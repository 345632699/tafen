<template>
  <el-upload
      class="upload-demo"
      action="http://www.tafen.com/banner/upload"
      :on-preview="handlePreview"
      :on-remove="handleRemove"
      :headers="scrfHeader"
      :file-list="fileList2"
      :data="uploadData"
      list-type="picture">
    <el-button size="small" type="primary">点击上传</el-button>
    <div slot="tip" class="el-upload__tip">只能上传jpg/png文件，且不超过500kb</div>
  </el-upload>
</template>
<script>
  export default {
    data() {
      return {
        fileList2: [{
          name: 'food.jpeg',
          url: 'https://fuss10.elemecdn.com/3/63/4e7f3a15429bfda99bce42a18cdd1jpeg.jpeg?imageMogr2/thumbnail/360x360/format/webp/quality/100'
        }, {
          name: 'food2.jpeg',
          url: 'https://fuss10.elemecdn.com/3/63/4e7f3a15429bfda99bce42a18cdd1jpeg.jpeg?imageMogr2/thumbnail/360x360/format/webp/quality/100'
        }],
        scrfHeader: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        uploadData: {
          'banner_type': 2
        }
      };
    },
    methods: {
      handleRemove(file, fileList) {
        console.log(file, fileList);
      },
      handlePreview(file) {
        console.log(file);
      }
    }
  }
</script>