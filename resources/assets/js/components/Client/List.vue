<template>
  <div>
    <el-table
        :data="client_list"
        style="width: 100%">

      <el-table-column
          label="姓名"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.nick_name }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="open_id"
      >
        <template slot-scope="scope">
          <span>{{ scope.row.open_id }}</span>
        </template>
      </el-table-column>
      <el-table-column
          label="可用余额"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.active == 0">{{ scope.row.amount / 100 }}（元）</span>
          <el-input v-else v-model="form.amount" placeholder="可用余额"></el-input>
        </template>
      </el-table-column>
      <el-table-column
          label="冻结金额"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.active == 0">{{ scope.row.freezing_amount / 100 }}（元）</span>
          <el-input v-else v-model="form.freezing_amount" placeholder="冻结金额"></el-input>
        </template>
      </el-table-column>
      <el-table-column
          label="总收入"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.active == 0">{{ scope.row.sum_money / 100 }}（元）</span>
          <el-input v-else v-model="form.sum_money" placeholder="总收入"></el-input>
        </template>
      </el-table-column>
      <el-table-column
          label="代理等级"
      >
        <template slot-scope="scope">
          <span v-if="scope.row.agent_type_id == 0 && scope.row.active == 0">普通用户</span>
          <span v-else-if="scope.row.agent_type_id == 1 && scope.row.active == 0">芬赚达人</span>
          <span v-else-if="scope.row.agent_type_id == 2 && scope.row.active == 0">芬赚高手</span>
          <span v-else-if="scope.row.agent_type_id == 3 && scope.row.active == 0">芬赚大师</span>
          <span v-else-if="scope.row.active == 0">员工</span>
          <el-select v-model="form.agent_type_id" placeholder="请选择" v-if="scope.row.active">
            <el-option
                v-for="item in options"
                :key="item.value"
                :label="item.label"
                :value="item.value">
            </el-option>
          </el-select>
        </template>
      </el-table-column>
      <el-table-column
          label="注册时间"
          width="200">
        <template slot-scope="scope">
          <i class="el-icon-time"></i>
          <span style="margin-left: 10px">{{ scope.row.created_at }}</span>
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
        client_list: [],
        selected: 0,
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
        console.log(index, row);
      },
      handleConfirm(index, row) {
        let that = this
        axios.post('/api/client/update', this.form).then(function (response) {
          if (response.data.status) {
            that.getClientList()
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
      getClientList() {
        let that = this
        axios.get('/api/clientList').then(function (response) {
          let client_list = response.data.clients
          client_list.forEach(function (item, index) {
            client_list[index].active = 0
          })
          that.client_list = client_list
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
      this.getClientList()
    }
  }
</script>

<style scoped>

</style>