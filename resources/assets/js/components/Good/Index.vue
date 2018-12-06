<template>
    <div class="container">
        <h1>商品详情</h1>
        <el-container style=" border: 1px solid #eee">
            <el-form ref="form" label-width="80px" style="margin: 10px">
                <el-form-item label="商品名称">
                    3333
                </el-form-item>
                <el-form-item label="商品描述">
                    3333
                </el-form-item>
                <el-form-item label="商品描述">
                    3333
                </el-form-item>
                <el-form-item label="商品规格">
                    <el-tag>标签一</el-tag>
                    <el-tag>标签一</el-tag>
                    <el-tag>标签一</el-tag>
                    <el-tag>标签一</el-tag>
                </el-form-item>
                <!--<el-tag>标签一</el-tag>-->

                <el-form-item label="banner图">
                    <el-carousel indicator-position="outside" style="width: 500px">
                        <el-carousel-item v-for="item in 4" :key="item">
                            <h3>{{ item }}</h3>
                        </el-carousel-item>
                    </el-carousel>
                </el-form-item>
                <el-form-item label="详情图">
                    <el-carousel indicator-position="outside" style="width: 500px;" height="600px">
                        <el-carousel-item v-for="item in 4" :key="item">
                            <h3>{{ item }}</h3>
                        </el-carousel-item>
                    </el-carousel>
                </el-form-item>
            </el-form>
        </el-container>
    </div>
</template>

<script>
	export default {
		data() {
			return {
				currentDate: new Date()
			};
		},
		mounted() {
			console.log('Component mounted.')
		}
	}
</script>
<style lang="less">
    .el-carousel__item h3 {
        color: #475669;
        font-size: 14px;
        opacity: 0.75;
        line-height: 150px;
        margin: 0;
    }

    .el-carousel__item:nth-child(2n) {
        background-color: #99a9bf;
    }

    .el-carousel__item:nth-child(2n+1) {
        background-color: #d3dce6;
    }
    .container {
        width: 1280px;
        margin: auto;
    }

    .time {
        font-size: 13px;
        color: #999;
    }

    .bottom {
        margin-top: 13px;
        line-height: 12px;
    }

    .button {
        padding: 0;
        float: right;
    }

    .image {
        width: 100%;
        display: block;
    }

    .clearfix:before,
    .clearfix:after {
        display: table;
        content: "";
    }

    .clearfix:after {
        clear: both
    }
</style>
