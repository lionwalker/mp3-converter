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
                <div class="pt-4" v-show="this.in_progress">
                    <div class="demo-preview">
                        <div class="progress progress-striped active">
                            <div role="progressbar" style="width: 100%;" class="progress-bar"><span>Converting . . .</span></div>
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
            in_progress: false,
        }
    },

    methods: {
        onFileChange(e) {
            this.file_url = ''
            this.completed = false
            this.form.file = e.target.files[0];
        },
        submit() {
            this.file_url = ''
            this.completed = false
            this.in_progress = true

            let currentObj = this;
            const config = {
                headers: {'content-type': 'multipart/form-data'}
            }
            let formData = new FormData();
            formData.append('file', this.form.file);
            formData.append('name', this.form.name);
            axios.post(this.route('convert'), formData, config)
                .then(response => (
                    this.file_url = response.data, this.completed = true, this.in_progress = false, this.getHistoryData()
                ))
                .catch(function (error) {
                    currentObj.output = error;
                });

        },
        getHistoryData() {
            axios.post(this.route('history')).then(response => (this.histories = response.data))
        }
    },

    mounted() {
        this.getHistoryData()
    }
}
</script>

<style>
.demo-preview {
    padding-top: 10px;
    padding-bottom: 10px;
    margin: auto;
    width: 100%;
    text-align: center;
    border-radius: 15px;
}

.progress {
    background-color: #f5f5f5;
    border-radius: 3px;
    box-shadow: none;
}
.progress.progress-xs {
    height: 5px;
    margin-top: 5px;
}
.progress.progress-sm {
    height: 10px;
    margin-top: 5px;
}
.progress.progress-lg {
    height: 25px;
}
.progress.vertical {
    position: relative;
    width: 20px;
    height: 200px;
    display: inline-block;
    margin-right: 10px;
}
.progress.vertical > .progress-bar {
    width: 100% !important;
    position: absolute;
    bottom: 0;
}
.progress.vertical.progress-xs {
    width: 5px;
    margin-top: 5px;
}
.progress.vertical.progress-sm {
    width: 10px;
    margin-top: 5px;
}
.progress.vertical.progress-lg {
    width: 30px;
}

.progress-bar {
    background-color: #2196f3;
    box-shadow: none;
}
.progress-bar.text-left {
    text-align: left;
}
.progress-bar.text-left span {
    margin-left: 10px;
}
.progress-bar.text-right {
    text-align: right;
}
.progress-bar.text-right span {
    margin-right: 10px;
}

@-webkit-keyframes progress-bar-stripes {
    from {
        background-position: 40px 0;
    }
    to {
        background-position: 0 0;
    }
}
@keyframes progress-bar-stripes {
    from {
        background-position: 40px 0;
    }
    to {
        background-position: 0 0;
    }
}
.progress.active .progress-bar,
.progress-bar.active {
    -webkit-animation: progress-bar-stripes 2s linear infinite;
    -o-animation: progress-bar-stripes 2s linear infinite;
    animation: progress-bar-stripes 2s linear infinite;
}

.progress-striped .progress-bar,
.progress-bar-striped {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-size: 40px 40px;
}

.progress-bar-secondary {
    background-color: #323a45;
}

.progress-bar-default {
    background-color: #b0bec5;
}

.progress-bar-success {
    background-color: #64dd17;
}

.progress-bar-info {
    background-color: #29b6f6;
}

.progress-bar-warning {
    background-color: #ffd600;
}

.progress-bar-danger {
    background-color: #ef1c1c;
}
</style>
