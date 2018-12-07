<template>
  <div class="client-list">
    <h1>属性列表</h1>
    <el-button @click.native="addMain(1)" type="primary">添加属性</el-button>
    <el-table
        :data="good_attr_list"
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
          label="属性名称"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.attr_name }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="属性描述"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.description }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="属性值"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.name }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="原价"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.original_price / 100}} 元</span>
        </template>
      </el-table-column>
      <el-table-column
          label="是否优惠"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.is_coupon > 0 ? "是" : "否" }} </span>
        </template>
      </el-table-column>
      <el-table-column
          label="优惠价"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.discount_price / 100 }} 元</span>
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
    <!-- Form -->
    <el-dialog title="编辑图片" :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item label="属性类别" :label-width="formLabelWidth">
          <el-select v-model="form.attr_id" :disabled="form.attr_id > 0 ? true : false" placeholder="请选择"
                     @change="getDesscription">
            <el-option v-for="item,index in attrbute_List" :key="index" :label="item.name"
                       :value="item.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="属性描述" :label-width="formLabelWidth">
          <el-input disabled v-model="form.description" auto-complete="off"></el-input>
        </el-form-item>
        <el-form-item label="属性值" :label-width="formLabelWidth">
          <el-input v-model="form.name" auto-complete="off"></el-input>
        </el-form-item>
        <el-form-item label="原价" :label-width="formLabelWidth">
          <el-input v-model="form.original_price" auto-complete="off"></el-input>
        </el-form-item>
        <el-form-item label="优惠价" :label-width="formLabelWidth">
          <el-input v-model="form.discount_price" auto-complete="off"></el-input>
        </el-form-item>
        <el-form-item label="是否优惠" :label-width="formLabelWidth">
          <el-select v-model="form.is_coupon" placeholder="请选择">
            <el-option v-for="item,index in option_list" :key="index" :label="item.name"
                       :value="item.id"></el-option>
          </el-select>
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
        good_attr_list: [],
        attrbute_List: [],
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
          attr_id: '',
          attr_name: '',
          good_id: '',
          description: '',
          id: '',
          is_coupon: '',
          name: '',
          original_price: '',
          discount_price: '',
          index: ''
        },
        option_list: [
          {
            id: 1,
            name: "是"
          },
          {
            id: 0,
            name: "否"
          }
        ],
        is_create: false,
        formLabelWidth: '120px'
      }
    },
    methods: {
      addMain (type) {
        let attr_id = ''
        let description = ''
        if (this.good_attr_list.length > 0) {
          attr_id = this.good_attr_list[0].attr_id
          description = this.attrbute_List[this.good_attr_list[0].attr_id].description
        }
        this.form = {
          attr_id: attr_id,
          attr_name: '',
          good_id: '',
          description: description,
          id: '',
          is_coupon: '',
          name: '',
          original_price: '',
          index: ''
        }
        this.is_create = true
        this.uploadData.good_type = type
        this.dialogFormVisible = true
      },
      getDesscription (e) {
        this.form.description = this.attrbute_List[e - 1].description
      },
      handleEdit(index, row, type) {
        this.uploadData.good_type = type
        this.dialogFormVisible = true
        this.form.attr_id = row.attr_id
        this.form.description = row.description
        this.form.discount_price = row.discount_price / 100
        this.form.good_id = row.good_id
        this.form.id = row.id
        this.form.is_coupon = row.is_coupon
        this.form.name = row.name
        this.form.original_price = row.original_price / 100
        this.form.index = index
      },
      handleDelete(index, row, type) {
        let that = this
        let query = {
          id: row.id,
        }
        axios.post('/api/good/delAttr', query).then(function (response) {
          if (response.data.status) {
            that.getAttrInfo()
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
          attr_id: this.good_attr_list[0].attr_id,
          attr_name: '',
          good_id: '',
          description: this.attrbute_List[this.good_attr_list[0].attr_id].description,
          id: '',
          is_coupon: '',
          name: '',
          original_price: '',
          index: ''
        }
        this.fileList = []
      },
      addAttr(){
        let that = this
        this.is_create = false
        let good_id = this.$route.query.good_id
        let query = {
          'attr_id': that.form.attr_id,
          'discount_price': that.form.discount_price * 100,
          'good_id': good_id,
          'is_coupon': that.form.is_coupon,
          'name': that.form.name,
          'original_price': that.form.original_price * 100,
        }
        axios.post('/api/good/addAttr', query).then(function (response) {
          if (response.data.status > 0) {
            that.$notify({
              title: '成功',
              message: response.data.msg,
              type: 'success'
            })
            that.getAttrInfo()
            that.form = {
              url: '',
              order_by: '',
              description: '',
              index: ''
            }
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
      handleConfirm() {
        let that = this
        that.dialogFormVisible = false
        this.fileList = []
        if (this.is_create) {
          this.addAttr()
        } else {
          let item = that.good_attr_list[that.form.index]
          let query = {
            'attr_id': item.attr_id,
            'discount_price': that.form.discount_price * 100,
            'good_id': that.form.good_id,
            'id': that.form.id,
            'is_coupon': that.form.is_coupon,
            'name': that.form.name,
            'original_price': that.form.original_price * 100,
          }

          that.form = {
            url: '',
            order_by: '',
            description: '',
            index: ''
          }
          axios.post('/api/good/attrUpdate', query).then(function (response) {
            if (response.data.status > 0) {
              that.$notify({
                title: '成功',
                message: response.data.msg,
                type: 'success'
              })
              that.getAttrInfo()
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
      getAttrInfo() {
        let good_id = this.$route.query.good_id
        let that = this
        axios.get('/api/good/attributeList?good_id=' + good_id).then(function (response) {
          that.good_attr_list = response.data.data.good_attr_list
          that.attrbute_List = response.data.data.attr_list
          console.log(that.attrbute_List)
        }).catch((err) => {
          let res = err.response.data
          if (res.message == "Unauthenticated.") {
            // this.$router.push({path:'/login'})
            window.location.href = '/login'
          }
          console.log(err.response.data);
        });
      }
    },
    mounted() {

    },
    created() {
      this.getAttrInfo()
    }
  }
</script>

<style scoped>
  .client-list {
    max-width: 980px;
    margin: auto;
  }
</style>