<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Example Component</div>

                    <div class="panel-body">
                        I'm an example component!333
                    </div>
                    <h1>Hello, Larvuent!</h1>
                    <el-button @click="visible = true">按钮</el-button>
                    <el-dialog v-model="visible">
                        <p>欢迎使用 Element</p>
                    </el-dialog>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
