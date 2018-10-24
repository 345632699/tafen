<template>
  <div>
    <h1>广告列表</h1>
    <el-button @click.native="addBanner">添加广告</el-button>
    <el-table
        :data="banner_list"
        style="width: 100%">
      <el-table-column
          label="广告类型"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.name }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="预览"
      >
        <template slot-scope="scope">
          <span>
            <img :src="scope.row.img_url" width="80" height="50" alt="">
          </span>
        </template>
      </el-table-column>
      <el-table-column
          label="排序"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.active == 0">{{ scope.row.sort }}</span>
          <el-input v-else v-model="form.sort" placeholder="排序"></el-input>
        </template>
      </el-table-column>
      <el-table-column
          label="是否展示"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.is_display == 0 && scope.row.active == 0">否</span>
          <span v-else-if="scope.row.is_display == 1 && scope.row.active == 0">是</span>
          <el-select v-model="form.is_display" placeholder="请选择" v-if="scope.row.active">
            <el-option
                v-for="item in options"
                :key="item.value"
                :label="item.label"
                :value="item.value">
            </el-option>
          </el-select>
        </template>
      </el-table-column>
      <el-table-column label="操作">
        <template slot-scope="scope">
          <el-button
              v-if="scope.row.active == 0"
              size="mini"
              @click="handleEdit(scope.$index, scope.row)">编辑
          </el-button>
          <el-button
              v-else
              size="mini"
              @click="handleConfirm(scope.$index, scope.row)">确定
          </el-button>
          <el-button
              size="mini"
              type="danger"
              @click="handleDelete(scope.$index, scope.row)">删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
  export default {
    name: "list",
    data() {
      return {
        banner_list: [],
        selected: 0,
        form: {
          is_display: 0,
          sort: 0
        },
        options: [
          {
            value: 0,
            label: '否'
          },
          {
            value: 1,
            label: '是'
          }
        ]
      }
    },
    methods: {
      addBanner () {
        this.$router.push({path: '/banner/upload'})
      },
      handleEdit(index, row) {
        console.log(index, row)
        this.form.is_display = row.is_display
        this.form.sort = row.sort
        this.form.id = row.id
        this.banner_list[index].active = 1
      },
      handleDelete(index, row) {
        console.log(index, row);
        let that = this
        axios.post('/api/banner/delete', {id: row.id}).then(function (response) {
          if (response.data.status) {
            that.getBannerList()
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
      handleConfirm(index, row) {
        let that = this
        axios.post('/api/banner/update', this.form).then(function (response) {
          if (response.data.status) {
            that.getBannerList()
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
        this.client_list[index].active = 0
      },
      getBannerList() {
        let that = this
        axios.get('/api/banner/list').then(function (response) {
          let banner_list = response.data.data
          banner_list.forEach(function (item, index) {
            banner_list[index].active = 0
          })
          that.banner_list = banner_list
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
      this.getBannerList()
    }
  }
</script>

<style scoped>

</style>