<template>
  <div class="upload">
    <h1>背景图上传</h1>
    <el-form ref="form" :model="form" label-width="80px">
      <el-form-item label="背景类型">
        <el-select v-model="form.banner_type_id" placeholder="请选择" @change="typeChange">
          <el-option v-for="item,index in banner_type_list" :key="index" :label="item.name"
                     :value="item.id"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item label="是否展示">
        <el-switch v-model="form.is_display"></el-switch>
      </el-form-item>
      <el-form-item label="广告排序">
        <el-input type="text" style="width:200px" v-model="form.sort" placeholder="填写数字排序"></el-input>
      </el-form-item>
      <el-form-item label="关联商品">
        <el-select v-model="form.jump_url" placeholder="请选择">
          <el-option v-for="item,index in good_list" :key="index" :label="item.name" :value="item.uid"></el-option>
          <el-option label="课程" value="-1"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item label="广告图片">
        <el-upload
            class="upload-demo"
            action="http://www.tafen.com/banner/upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            :on-success="handleSuccess"
            :headers="scrfHeader"
            :file-list="fileList2"
            :data="uploadData"
            :limit="1"
            list-type="picture">
          <el-button size="small" type="primary">点击上传</el-button>
          <div slot="tip" class="el-upload__tip">只能上传jpg/png文件，且不超过500kb</div>
        </el-upload>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="onSubmit">立即创建</el-button>
        <el-button>取消</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>
<script>
  export default {
    data() {
      return {
        fileList2: [],
        good_list: '',
        banner_type_list: '',
        scrfHeader: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        uploadData: {
          'banner_type': 2
        },
        form: {
          banner_type_id: '',
          is_display: '',
          sort: '',
          jump_url: '',
          img_url: ''
        }
      };
    },
    methods: {
      typeChange (e) {
        this.uploadData.banner_type = e
      },
      handleRemove(file, fileList) {
        console.log(file, fileList);
      },
      handlePreview(file) {
        console.log(file);
      },
      handleSuccess(response, file, fileList){
        this.form.img_url = response.path
        console.log(response.path)
      },
      onSubmit() {
        let that = this
        axios.post('/api/banner/create', this.form).then(function (response) {
          if (response.data.status) {
            that.$notify({
              title: '成功',
              message: response.data.msg,
              type: 'success'
            })
          } else {
            that.$notify.error({
              title: '错误',
              message: response.data.msg
            });
          }
        }).catch((err) => {
          console.log(err)
          that.$notify.error({
            title: '错误',
            message: err
          });
        })
      }
    },
    created () {
      let that = this
      axios.get('/api/banner/goods').then(function (response) {
        if (response.data.status) {
          that.good_list = response.data.data
        }
      }).catch((err) => {
        let res = err.response.data
        if (res.message == "Unauthenticated.") {
          // this.$router.push({path:'/login'})
          window.location.href = '/login'
        }
        console.log(err.response.data);
      });
      axios.get('/api/banner/typeList').then(function (response) {
        if (response.data.status) {
          that.banner_type_list = response.data.data
        }
      }).catch((err) => {
        let res = err.response.data
        if (res.message == "Unauthenticated.") {
          // this.$router.push({path:'/login'})
          window.location.href = '/login'
        }
        console.log(err.response.data);
      });
    }
  }
</script>
<style>
  .upload {
    max-width: 980px;
    margin: 20px;
    margin: auto;
  }
</style>