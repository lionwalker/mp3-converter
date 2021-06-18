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

        $api_key = env('CLOUDCONVERT_API_KEY','eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMjlhZGRlNTBkMDAyZWYxMWVmODEzNDA0ZThhNTFjZDRhZGEwODI5Yzg5ZDIwMzQ0MTU1ZGI0ZmQzNTU3NDg4ZWUyZDRhYzUyYTk0MDEwNWUiLCJpYXQiOjE2MjM4NTM0MTYuNTQ1NzUxLCJuYmYiOjE2MjM4NTM0MTYuNTQ1NzU0LCJleHAiOjQ3Nzk1MjcwMTYuNTA3OTM2LCJzdWIiOiI1MTgwMDA4NyIsInNjb3BlcyI6WyJ1c2VyLnJlYWQiLCJ1c2VyLndyaXRlIiwidGFzay5yZWFkIiwidGFzay53cml0ZSIsIndlYmhvb2sucmVhZCIsIndlYmhvb2sud3JpdGUiLCJwcmVzZXQucmVhZCIsInByZXNldC53cml0ZSJdfQ.ExzpPQJ3LZrtH8nlJ02CSR1us8U3uPw3faMV4VM1hWZImGO7dT8X2Ioe2Q2UIy5-NxPRtEuzQNfiObYU3GmI1tnUmYR_csBipuL8uVwfTkJhH41faJDXs8SVAUzGKwifUAGMQFtdDpacUyLFRGCwtQx-K8m2vaEk1hmAcoY29c8mHdtDQb58jbpajByOgeLuVAl0e7TJlEEbvacY1-is1FkCkhY1_UX2HjNfz6RdafqPfIol8ZWuHsXfY5jKCdU2NinH-tPm9PbvamhCKqGCIc9MDPb_Wzq1hcJBanMKw7q6Cehf__u6zCsSktU__tDcP-13wmSQhwSyPGCngYh2bI6RCGg-fjSmEL49yETajpa3xKYmf7xnPxHonNLG0tAAANJXiYeRMnaSXhg9W0BOPAsSI1zt4R94GHLiL1wISwg8ofIy3pLIUPRyVdIoWHy379k03DBwmTcZN8iaWrM788JLOUML5Phx9mw8CS0a1bLWHiZxByf2foiTRwzGMMk_A3r-kaHGEUff-PkAVUipInmUlKg6h_dy9BL7OR29xyWW1q3OJjoRJvBXmQ2JQFxfD7KfiK33oShHtxtnJpVI13FZU8g9T0UBm1hawE_q0Cu3BSBfsYcn65CoPuPhIviyF3RyxFvzLPpJ75w-WNB2c1Yk8Rh19v7cg12dWXj4Z3A');
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
        $signingSecret = 'oVJrVlJee43XjxzjtJty8h1N8AwyW7lF';
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
