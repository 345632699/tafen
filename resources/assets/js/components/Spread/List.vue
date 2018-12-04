<template>
  <div class="client-list">
    <h1>用户列表</h1>
    <el-table
        :data="spread_list"
        style="width: 100%">

      <el-table-column
          label="姓名"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.nick_name }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="审核记录"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.memo }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="金额"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.amount / 100 }}（元）</span>
        </template>
      </el-table-column>
      <el-table-column
          label="审核状态"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.type == 2">待审核</span>
          <span v-else-if="scope.row.type == 3">已入账</span>
          <span v-else-if="scope.row.type == 0">审核未通过</span>
        </template>
      </el-table-column>
      <el-table-column
          label="提现时间"
          width="200">
        <template slot-scope="scope">
          <i class="el-icon-time"></i>
          <span style="margin-left: 10px">{{ scope.row.created_at }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作">
        <template slot-scope="scope">
          <el-button
              v-if="scope.row.type == 2"
              size="mini"
              @click="handleConfirm(scope.$index, scope.row)">确定
          </el-button>
          <el-button
              v-if="scope.row.type == 2"
              size="mini"
              type="danger"
              @click="handleDelete(scope.$index, scope.row)">拒绝
          </el-button>
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
        spread_list: [],
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
      handleEdit(index, row) {
        console.log(index, row)
        this.form.agent_type_id = row.agent_type_id
        this.form.amount = row.amount / 100
        this.form.freezing_amount = row.freezing_amount / 100
        this.form.sum_money = row.sum_money / 100
        this.form.id = row.id
        this.client_list[index].active = 1
      },
      handleDelete(index, row) {
        let that = this
        let query = {
          id: row.uid,
          type: 2,
          client_id: row.client_id,
          amount: row.amount
        }
        axios.post('/api/spread/update', query).then(function (response) {
          if (response.data.status) {
            that.getSpreadList()
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
        axios.post('/api/spread/update', query).then(function (response) {
          if (response.data.status) {
            that.getSpreadList()
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
      getSpreadList(page = 1) {
        let that = this
        axios.get('/api/spread/list?page=' + page).then(function (response) {
          that.spread_list = response.data.data.data
          this.last_page = response.data.data.last_page
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
        this.getSpreadList(page)
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
        this.getSpreadList(page)
        this.page = page
      }
    },
    mounted() {

    },
    created() {
      this.getSpreadList()
    }
  }
</script>

<style scoped>
  .client-list {
    max-width: 1280px;
    margin: auto;
  }
</style>