<template>
    <div class="client-list">
        <h1>用户列表</h1>
        <el-table
                :data="withdraw_list"
                style="width: 100%">

            <el-table-column
                    label="姓名"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.nick_name }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="提现订单号"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.partner_trade_no }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="提现金额"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.amount / 100 }}（元）</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="提现状态"
            >
                <template slot-scope="scope">
                    <span v-if="scope.row.status == 0">提现失败</span>
                    <span v-else-if="scope.row.status == 1">提现成功</span>
                    <span v-else-if="scope.row.status == 2">提现中</span>
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
                            v-if="scope.row.status == 2"
                            size="mini"
                            @click="handleConfirm(scope.$index, scope.row)">确定
                    </el-button>
                    <el-button
                            v-if="scope.row.status == 2"
                            size="mini"
                            type="danger"
                            @click="handleDelete(scope.$index, scope.row)">拒绝
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
				withdraw_list: [],
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
				let that = this
				let query = {
					id: row.uid,
					type: 0,
					client_id: row.client_id,
					amount: row.amount
				}
				axios.post('/api/withdraw/operate', query).then(function (response) {
					if (response.data.status) {
						this.getWithdrawList()
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
				axios.post('/api/withdraw/operate', query).then(function (response) {
					if (response.data.status) {
						this.getWithdrawList()
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
			getWithdrawList() {
				let that = this
				axios.get('/api/withdraw/list').then(function (response) {
					that.withdraw_list = response.data.data
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
			this.getWithdrawList()
		}
	}
</script>

<style scoped>
    .client-list {
        max-width: 1280px;
        margin: auto;
    }
</style>