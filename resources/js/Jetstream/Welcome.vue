<template>
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
        <div class="mt-4 ml-4 mb-5 text-2xl">
            Welcome to MP3 Converter!
        </div>
        <div class="md:flex">
            <form @submit.prevent="submit" class="ml-4 mr-4">
                <div class="md:flex-shrink-0">
                    <jet-label for="name" value="Name"/>
                    <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name"/>
                </div>
                <div class="pt-4">
                    <jet-label for="file" value="File"/>
                    <jet-input id="file" type="file" class="mt-1 block w-full" @change="onFileChange" accept="audio/*" required/>
                </div>
                <div class="pt-4" v-show="this.progress_bar">
                    <div class="relative pt-1">
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-indigo-200">
                            <div :style="{width: this.progress + '%'}" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500"></div>
                        </div>
                    </div>
                </div>
                <div class="pt-4 pb-4">
                    <a v-show="this.completed" download="" :href="this.file_url" class="mr-2 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Download
                    </a>
                    <jet-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Convert
                    </jet-button>
                </div>
            </form>
        </div>
        <div>
            <div class="mt-4 ml-4 mb-5 text-2xl">
                History
            </div>
            <table class="min-w-full divide-y divide-gray-200 ml-4 mr-4 mb-5">
                <thead class="bg-gray-50 text-left">
                <tr>
                    <th scope="col">Time</th>
                    <th scope="col">Original name</th>
                    <th scope="col">After converted</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="history in histories" class="px-6 py-4 whitespace-nowrap">
                    <td>{{ history.created_at }}</td>
                    <td>{{ history.file_name }}</td>
                    <td><a :href="'/converted/' + history.after_name" download="">{{ history.after_name }}</a></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

</template>

<script>
import JetButton from '@/Jetstream/Button'
import JetInput from '@/Jetstream/Input'
import JetLabel from '@/Jetstream/Label'
import JetValidationErrors from '@/Jetstream/ValidationErrors'

export default {
    components: {
        JetButton,
        JetInput,
        JetLabel,
        JetValidationErrors,
    },

    data() {
        return {
            form: {
                name: '',
                file: '',
                processing: false
            },
            histories: [],
            completed: false,
            file_url: '',
            progress: 0,
            progress_bar: false,
            invervalSpeed: 200,
            incrementSpeed: 5,
            progressInterval: 0,
        }
    },

    methods: {
        onFileChange(e) {
            this.file_url = ''
            this.completed = false
            this.progress = 0
            this.form.file = e.target.files[0];
        },
        moveProgress()
        {
            let process = this.progress
            let speed = this.incrementSpeed
            console.log("1",this.progress)
            this.progressInterval = setInterval(function(){
                process = process + speed;
                this.progress = process
                console.log("2",this.progress)
                if(process >= 100){
                    process = 0
                    this.progress = 0
                    clearInterval(this.progressInterval);
                }
            }, this.invervalSpeed);
        },
        submit() {
            this.file_url = ''
            this.completed = false
            this.progress_bar = true
            this.progress = 0
            this.moveProgress()

            let currentObj = this;
            const config = {
                headers: {'content-type': 'multipart/form-data'}
            }
            let formData = new FormData();
            formData.append('file', this.form.file);
            formData.append('name', this.form.name);
            axios.post(this.route('convert'), formData, config)
                .then(response => (
                    this.file_url = response.data, this.completed = true, this.progress_bar = false, clearInterval(this.progressInterval)
                ))
                .catch(function (error) {
                    currentObj.output = error;
                });

        },
    },

    mounted() {
        axios.post(this.route('history')).then(response => (this.histories = response.data))
    }
}
</script>
