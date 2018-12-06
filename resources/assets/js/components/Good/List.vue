<template>
  <div class="client-list">
    <h1>商品列表</h1>
    <el-button @click.native="addGood" type="primary">添加商品</el-button>
    <el-table
        :data="good_list"
        style="width: 100%">

      <el-table-column
          label="商品名称"
          width="180"
      >
        <template slot-scope="scope">
          <!--<span>{{ scope.row.name }}</span>-->
          <span
              style="overflow: hidden;text-overflow: ellipsis;display: -webkit-box;-webkit-line-clamp: 3;-webkit-box-orient: vertical;">{{ scope.row.name
            }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="缩略图"
      >
        <template slot-scope="scope">
          <img :src="scope.row.thumbnail_img" alt="" width="100" height="80">
        </template>
      </el-table-column>
      <el-table-column
          label="商品描述"
          width="180"
      >
        <template slot-scope="scope">
            <span
                style="overflow: hidden;text-overflow: ellipsis;display: -webkit-box;-webkit-line-clamp: 3;-webkit-box-orient: vertical;">{{ scope.row.description
            }}</span>
          <!--<span>{{ scope.row.description }}</span>-->
        </template>
      </el-table-column>
      <el-table-column
          label="优惠"
          width="50"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.is_coupon == 1">是</span>
          <span v-else-if="scope.row.is_coupon == 0">否</span>
        </template>
      </el-table-column>
      <el-table-column
          label="商品原价"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.original_price / 100 }}（元）</span>
        </template>
      </el-table-column>
      <el-table-column
          label="优惠价"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.discount_price / 100 }}（元）</span>
        </template>
      </el-table-column>
      <el-table-column
          label="运费"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.delivery_fee / 100 }}（元）</span>
        </template>
      </el-table-column>
      <el-table-column
          label="上架时间"
          width="200">
        <template slot-scope="scope">
          <i class="el-icon-time"></i>
          <span style="margin-left: 10px">{{ scope.row.created_at }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="是否上架"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.is_onsale == 1">是</span>
          <span v-else-if="scope.row.is_onsale == 0">否</span>
        </template>
      </el-table-column>
      <el-table-column
          label="图片详情"
      >
        <template slot-scope="scope">
          <el-button
              size="mini"
              type="primary"
              @click="getDetail(scope.$index, scope.row)">查看
          </el-button>
        </template>
      </el-table-column>
      <el-table-column
          label="规格"
      >
        <template slot-scope="scope">
          <el-button
              size="mini"
              type="info"
              @click="">查看
          </el-button>
        </template>
      </el-table-column>
      <el-table-column label="操作" >
        <template slot-scope="scope">
          <el-button
              size="mini"
              type="warning"
              @click="handleEdit(scope.$index, scope.row)">编辑
          </el-button>
          <!--<el-button-->
              <!--size="mini"-->
              <!--type="danger"-->
              <!--@click="handleDelete(scope.$index, scope.row)">删除-->
          <!--</el-button>-->
        </template>
      </el-table-column>
    </el-table>
    <el-button @click="prePage">上一页</el-button>
    <el-button @click="nextPage">下一页</el-button>
  </div>
</template>

<script>
  export default {
    name: "list",
    data() {
      return {
        good_list: [],
        selected: 0,
        page: 1,
        last_page: 1,
        form: {
          agent_type_id: 0,
          sum_money: 0,
          amount: 0,
          freezing_amount: 0,
          id: 0
        },
        options: [
          {
            value: 0,
            label: '普通用户'
          },
          {
            value: 1,
            label: '芬赚达人'
          },
          {
            value: 2,
            label: '芬赚高手'
          },
          {
            value: 3,
            label: '芬赚大师'
          },
          {
            value: 10,
            label: '员工'
          }
        ]
      }
    },
    methods: {
      addGood () {
        this.$router.push({path: '/good/create'})
      },
			getDetail (index,row) {
      	this.$router.push("/good/banners?good_id=" + row.uid)
      },
      handleEdit(index, row) {
       this.$router.push('/good/edit?good_id=' + row.uid)
      },
      handleDelete(index, row) {
        let that = this
        let query = {
          id: row.uid,
          type: 2,
          client_id: row.client_id,
          amount: row.amount
        }
        axios.post('/api/good/update', query).then(function (response) {
          if (response.data.status) {
            that.getGoodList()
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
        let query = {
          id: row.uid,
          type: 1,
          client_id: row.client_id,
          amount: row.amount
        }
        axios.post('/api/good/update', query).then(function (response) {
          if (response.data.status) {
            that.getGoodList()
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
      getGoodList(page = 1) {
        let that = this
        axios.get('/api/good/list?page=' + page).then(function (response) {
          that.good_list = response.data.data.data
					that.last_page = response.data.data.last_page
        }).catch((err) => {
          let res = err.response.data
          if (res.message == "Unauthenticated.") {
            // this.$router.push({path:'/login'})
            window.location.href = '/login'
          }
          console.log(err.response.data);
        });
      },
      prePage () {
        let page = this.page - 1
        if (page <= 0) {
          page = 1
        }
        this.getGoodList(page)
        this.page = page
      },
      nextPage () {
        let page = this.page + 1
        if (page > this.last_page) {
          this.$notify.error({
            title: '错误',
            message: '最后一页了'
          });
          return
        }
        this.getGoodList(page)
        this.page = page
      }
    },
    mounted() {

    },
    created() {
      this.getGoodList()
    }
  }
</script>

<style scoped>
  .client-list {
    max-width: 1280px;
    margin: auto;
  }
</style>