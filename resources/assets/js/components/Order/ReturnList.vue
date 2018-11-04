<template>
    <div class="client-list">
        <h1>用户列表</h1>
        <el-table
                :data="return_list"
                style="width: 100%">

            <el-table-column
                    label="退货人"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.nick_name }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="退款单号"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.return_order_number }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="订单号"
            >
                    <template slot-scope="scope">
                        <span style="cursor: pointer;color: blue" @click="jump(scope.row.order_header_id)">{{ scope.row.order_number }}</span>
                    </template>
            </el-table-column>
            <el-table-column
                    label="退货类型"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.return_request_type == 0 ? '用户发起' : "平台发起" }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="退款状态"
            >
                <template slot-scope="scope">
                    <span v-if="scope.row.return_order_status == 0">提交申请</span>
                    <span v-else-if="scope.row.return_order_status == 1">审批拒绝</span>
                    <span v-else-if="scope.row.return_order_status == 2">审批通过</span>
                    <span v-else-if="scope.row.return_order_status == 3">退货中</span>
                    <span v-else-if="scope.row.return_order_status == 4">已完成</span>
                    <span v-else-if="scope.row.return_order_status == 5">异常</</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="退款理由类型"
            >
                <template slot-scope="scope">
                    <span v-if="scope.row.return_reason_type == 0">质量问题</span>
                    <span v-else-if="scope.row.return_reason_type == 1">7天无理由</span>
                    <span v-else-if="scope.row.return_reason_type == 2">选错商品</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="退款原因"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.return_reason }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="货物状态"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.good_status == 1 ? '已到货' : '未到货' }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="退款金额"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.return_sum }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="退款电话"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.return_phone }}</span>
                </template>
            </el-table-column>
            <el-table-column
                    label="退款地址"
            >
                <template slot-scope="scope">
                    <span>{{ scope.row.address }}</span>
                </template>
            </el-table-column>

            <el-table-column label="操作" width="160px">
                <template slot-scope="scope">
                    <el-button
                            v-if="scope.row.return_order_status == 0"
                            size="mini"
                            @click="handleConfirm(scope.$index, scope.row)">同意
                    </el-button>
                    <el-button
                            v-if="scope.row.return_order_status == 2"
                            size="mini"
                            @click="handleDone(scope.$index, scope.row)">完成
                    </el-button>
                    <el-button
                            v-if="scope.row.return_order_status == 0"
                            size="mini"
                            type="danger"
                            @click="handleReject(scope.$index, scope.row)">拒绝
                    </el-button>
                    <el-button
                            v-else
                            size="mini"
                            type="danger"
                            @click="handleReset(scope.$index, scope.row)">重置
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
				return_list: [],
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
			handleReset(index, row){
				let that = this
				console.log(row)
				let query = {
					id: row.uid,
					type: 0,
					order_id: row.order_header_id
				}

				axios.post('/api/return/operate', query).then(function (response) {
					if (response.data.status) {
						that.getReturnList()
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
			handleReject(index, row) {
				let that = this
				console.log(row)
				let query = {
					id: row.uid,
					type: 1,
					order_id: row.order_header_id
				}

				axios.post('/api/return/operate', query).then(function (response) {
					if (response.data.status) {
						that.getReturnList()
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
			handleDone (index, row){
				let that = this
				console.log(row)
				let query = {
					id: row.uid,
					type: 4,
					order_id: row.order_header_id
				}

				axios.post('/api/return/operate', query).then(function (response) {
					if (response.data.status) {
						that.getReturnList()
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
                console.log(row)
                let query = {
					id: row.uid,
					type: 2,
					order_id: row.order_header_id
                }

				axios.post('/api/return/operate', query).then(function (response) {
					console.log(response.data.status)
					if (response.data.status) {
						that.getReturnList()
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
			jump (id) {
				console.log(id);
				location.href = '/order/' + id
            },
			getReturnList() {
				let that = this
				axios.get('/api/return/list').then(function (response) {
					that.return_list = response.data.data
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
			this.getReturnList()
		}
	}
</script>

<style scoped>
    .client-list {
        max-width: 1280px;
        margin: auto;
    }
</style>