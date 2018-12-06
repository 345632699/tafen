<template>
  <div class="client-list">
    <h1>商品主图</h1>
    <el-button @click.native="addMain(1)" type="primary">添加主图</el-button>
    <el-table
        :data="banner_List"
        style="width: 100%">

      <el-table-column
          label="商品名称"
      >
        <template slot-scope="scope">
          <span
              style="overflow: hidden;text-overflow: ellipsis;display: -webkit-box;-webkit-line-clamp: 2;-webkit-box-orient: vertical;">{{ scope.row.good_name
            }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="主图展示"
      >
        <template slot-scope="scope">
          <img :src="scope.row.url" alt="" width="100" height="80"/>
        </template>
      </el-table-column>
      <el-table-column
          label="排序"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.order_by }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="图片描述"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.description }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="180">
        <template slot-scope="scope">
          <el-button
              size="mini"
              @click="handleEdit(scope.$index, scope.row, 1)">编辑
          </el-button>
          <el-button
              size="mini"
              type="danger"
              @click="handleDelete(scope.$index, scope.row, 1)">删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    <h1>商品详情图</h1>
    <el-button @click.native="addMain(2)" type="primary">添加详情图</el-button>
    <el-table
        :data="detail_img_list"
        style="width: 100%">

      <el-table-column
          label="商品名称"
      >
        <template slot-scope="scope">
          <span
              style="overflow: hidden;text-overflow: ellipsis;display: -webkit-box;-webkit-line-clamp: 2;-webkit-box-orient: vertical;">{{ scope.row.good_name
            }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="主图展示"
      >
        <template slot-scope="scope">
          <img :src="scope.row.url" alt="" width="100" height="80"/>
        </template>
      </el-table-column>
      <el-table-column
          label="排序"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.order_by }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="图片描述"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.description }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="180">
        <template slot-scope="scope">
          <el-button
              size="mini"
              @click="handleEdit(scope.$index, scope.row, 2)">编辑
          </el-button>
          <el-button
              size="mini"
              type="danger"
              @click="handleDelete(scope.$index, scope.row, 2)">删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    <!-- Form -->
    <el-dialog title="编辑图片" :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item label="图片预览" :label-width="formLabelWidth">
          <img :src="form.url" alt="" style="width:80%"/>
        </el-form-item>
        <el-form-item label="图片地址" :label-width="formLabelWidth">
          <el-input disabled v-model="form.url" auto-complete="off"></el-input>
        </el-form-item>
        <el-form-item label="图片描述" :label-width="formLabelWidth">
          <el-input v-model="form.description" auto-complete="off"></el-input>
        </el-form-item>
        <el-form-item label="图片排序" :label-width="formLabelWidth">
          <el-input v-model="form.order_by" auto-complete="off"></el-input>
        </el-form-item>
        <el-form-item label="图片更换" :label-width="formLabelWidth">
          <el-upload
              class="upload-demo"
              action="http://www.tafen.com/good/upload"
              :on-preview="handlePreview"
              :on-remove="handleRemove"
              :on-success="handleSuccess"
              :headers="scrfHeader"
              :file-list="fileList"
              :data="uploadData"
              :limit="1"
              list-type="picture">
            <el-button size="small" type="primary">点击更换</el-button>
            <div slot="tip" class="el-upload__tip">只能上传jpg/png文件，且不超过500kb</div>
          </el-upload>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="cancelEdit">取 消</el-button>
        <el-button type="primary" @click="handleConfirm">确 定</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
  export default {
    data() {
      return {
        banner_List: [],
        detail_img_list: [],
        selected: 0,
        page: 1,
        last_page: 1,
        dialogFormVisible: false,
        scrfHeader: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        uploadData: {
          'good_type': 1
        },
        fileList: [],
        form: {
          url: '',
          order_by: '',
          description: '',
          index: ''
        },
        is_create: false,
        formLabelWidth: '120px'
      }
    },
    methods: {
      addMain (type) {
				this.form = {
					url: '',
					order_by: 1,
					description: '',
					index: ''
				}
        this.is_create = true
        this.uploadData.good_type = type
        this.dialogFormVisible = true
      },
      handleEdit(index, row, type) {
        this.uploadData.good_type = type
        this.dialogFormVisible = true
        this.form.description = row.description
        this.form.url = row.url
        this.form.order_by = row.order_by
        this.form.index = index
      },
      handleDelete(index, row, type) {
        let that = this
        let query = {
          id: row.uid,
          good_type: type
        }
        axios.post('/api/good/deleteImg', query).then(function (response) {
          if (response.data.status) {
            that.getGoodImgs()
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
      },
      cancelEdit (){
				this.is_create = false
				this.dialogFormVisible = false
				this.form = {
					url: '',
					order_by: '',
					description: '',
					index: ''
				}
				this.fileList = []
      },
      addImg(){
      	let that = this
      	this.is_create = false
				let good_id = this.$route.query.good_id
				let query = {
      		url: this.form.url,
      		description: this.form.description,
      		order_by: this.form.order_by,
      		good_type: this.uploadData.good_type,
          good_id: good_id
        }
				axios.post('/api/good/addImg', query).then(function (response) {
					if (response.data.status > 0) {
						that.$notify({
							title: '成功',
							message: response.data.msg,
							type: 'success'
						})
						that.getGoodImgs()
						that.form = {
							url: '',
							order_by: '',
							description: '',
							index: ''
						}
					} else {
						that.$notify.error({
							title: '错误1',
							message: response.data.msg
						});
					}
				}).catch((err) => {
					console.log(err)
					that.$notify.error({
						title: '错误2',
						message: err
					});
				})
      },
      handleConfirm() {
				let that = this
				that.dialogFormVisible = false
				this.fileList = []
      	if (this.is_create){
      		this.addImg()
        }else{
					let item = []
					if (that.uploadData.good_type == 2) {
						item = that.detail_img_list[that.form.index]
					} else {
						item = that.banner_List[that.form.index]
					}
					let query = {
						'good_id': item.good_id,
						'id': item.uid,
						'type': that.uploadData.good_type,
						'description': that.form.description,
						'url': that.form.url,
						'order_by': that.form.order_by
					}
					that.form = {
						url: '',
						order_by: '',
						description: '',
						index: ''
					}
					axios.post('/api/good/imgUpdate', query).then(function (response) {
						if (response.data.status > 0) {
							that.$notify({
								title: '成功',
								message: response.data.msg,
								type: 'success'
							})
							that.getGoodImgs()
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
      getGoodImgs() {
        let good_id = this.$route.query.good_id
        let that = this
        axios.get('/api/good/bannerImgList?good_id=' + good_id).then(function (response) {
          that.banner_List = response.data.data
        }).catch((err) => {
          let res = err.response.data
          if (res.message == "Unauthenticated.") {
            // this.$router.push({path:'/login'})
            window.location.href = '/login'
          }
          console.log(err.response.data);
        });
        axios.get('/api/good/detailImgList?good_id=' + good_id).then(function (response) {
          that.detail_img_list = response.data.data
        }).catch((err) => {
          let res = err.response.data
          if (res.message == "Unauthenticated.") {
            // this.$router.push({path:'/login'})
            window.location.href = '/login'
          }
          console.log(err.response.data);
        });
      },
      handleRemove(file, fileList) {
        console.log(file, fileList);
      },
      handlePreview(file) {
        console.log(file);
      },
      beforeRemove(file, fileList) {
        return this.$confirm(`确定移除 ${ file.name }？`);
      },
      handleSuccess(response, file, fileList){
        this.form.url = response.path
        console.log(response)
      }
    },
    mounted() {

    },
    created() {
//      this.getGoodList()
      this.getGoodImgs()
    }
  }
</script>

<style scoped>
  .client-list {
    max-width: 980px;
    margin: auto;
  }
</style>