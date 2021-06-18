<?php

namespace App\Http\Controllers;

use App\Mail\ConversionFinished;
use App\Models\Converter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;


class ConverterController extends Controller
{
    //
    public function convertFile(Request $request)
    {

        $user_id = Auth::id();
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $originalExtension = $file->getClientOriginalExtension();
        $after_name = $request->name . ".mp3";
        $destinationPath = storage_path("app/uploads");
        $convertedFilePath = storage_path("app/converted");
        $file->move($destinationPath, $originalName);
        $convert = Converter::create([
            'file_name' => $originalName,
            'after_name' => $after_name,
            'user_id' => $user_id,
        ]);

        $api_key = env('CLOUDCONVERT_API_KEY','');
        $sandbox = env('CLOUDCONVERT_SANDBOX', true);
        $cloudconvert = new CloudConvert([
            'api_key' => $api_key,
            'sandbox' => $sandbox
        ]);
        $job = (new Job())
            ->addTask(new Task('import/upload',"import-1"))
            ->addTask(
                (new Task('convert', "task-1"))
                    ->set('input', "import-1")
                    ->set('input_format', $originalExtension)
                    ->set('output_format', 'mp3')
                    ->set('engine', 'ffmpeg')
            )
            ->addTask(
                (new Task('export/url', "export-1"))
                    ->set('input', "task-1")
                    ->set('inline', false)
                    ->set('archive_multiple_files', false)
            );

        $cloudconvert->jobs()->create($job);

        $uploadTask = $job->getTasks()->whereName("import-1")[0];
        $inputStream = fopen(Storage::path("uploads/$originalName"), 'r');
        $cloudconvert->tasks()->upload($uploadTask, $inputStream);

        $cloudconvert->jobs()->wait($job); // Wait for job completion

        foreach ($job->getExportUrls() as $file) {

            $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();
            $dest = fopen(public_path("converted/$after_name"), 'w');

            stream_copy_to_stream($source, $dest);

        }

        $email = Auth::user()->email;
        Mail::to($email)->send(new ConversionFinished());

        return "/converted/$after_name";
    }

    public function webhook()
    {
        $api_key = env('CLOUDCONVERT_API_KEY');
        $sandbox = env('CLOUDCONVERT_SANDBOX');
        $cloudconvert = new CloudConvert([
            'api_key' => $api_key,
            'sandbox' => $sandbox
        ]);
        $signingSecret = '';
        $payload = @file_get_contents('php://input');
        $signature = $_SERVER['HTTP_CLOUDCONVERT_SIGNATURE'];
        try {
            $webhookEvent = $cloudconvert->webhookHandler()->constructEvent($payload, $signature, $signingSecret);
        } catch(\CloudConvert\Exceptions\UnexpectedDataException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch(\CloudConvert\Exceptions\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        $job = $webhookEvent->getJob();
    }

    public function getHistory()
    {
        $user_id = Auth::id();
        $records = Converter::where('user_id','=',$user_id)->orderBy('created_at','desc')->get();

        return $records->toArray();
    }
}
