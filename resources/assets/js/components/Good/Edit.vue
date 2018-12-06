<template>
  <div class="good container">
    <h1>商品编辑</h1>
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default">
          <div class="panel-body">
            <el-form ref="form" :model="form" label-width="120px">
              <el-form-item label="商品">
                <el-input v-model="form.name"></el-input>
              </el-form-item>
              <el-form-item label="商品描述">
                <el-input type="textarea" v-model="form.description"></el-input>
              </el-form-item>
              <el-form-item label="原价">
                <el-input type="text" v-model="form.original_price" placeholder="单位（分）"></el-input>
              </el-form-item>
              <el-form-item label="是否优惠">
                <el-select v-model="form.is_coupon" placeholder="请选择活动区域">
                  <el-option v-for="item,index in option_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="优惠价格">
                <el-input type="text" v-model="form.discount_price" placeholder="单位（分）"></el-input>
              </el-form-item>
              <el-form-item label="库存">
                <el-input type="number" v-model="form.stock"></el-input>
              </el-form-item>
              <el-form-item label="商品分类">
                <el-select v-model="form.category_id" placeholder="请选择">
                  <el-option v-for="item,index in cat_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="是否上架">
                <el-select v-model="form.is_onsale" placeholder="请选择">
                  <el-option v-for="item,index in option_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="是否新品">
                <el-select v-model="form.is_new" placeholder="请选择">
                  <el-option v-for="item,index in option_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="是否热门">
                <el-select v-model="form.is_hot" placeholder="请选择">
                  <el-option v-for="item,index in option_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="是否代理商品">
                <el-select v-model="form.is_agent_type" placeholder="请选择">
                  <el-option v-for="item,index in option_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="关联代理等级">
                <el-select v-model="form.agent_type_id" placeholder="请选择">
                  <el-option v-for="item,index in agent_type_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="属性">
                <el-select v-model="form.attribute_id" placeholder="请选择">
                  <el-option v-for="item,index in attr_list" :key="index" :label="item.name"
                             :value="item.id"></el-option>
                </el-select>
              </el-form-item>
              <el-form-item label="运费">
                <el-input type="number" v-model="form.delivery_fee" placeholder="单位（分）"></el-input>
              </el-form-item>
              <el-form-item label="商品排序">
                <el-input type="number" v-model="form.sort"></el-input>
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="onSubmit">保存修改</el-button>
                <el-button @click.native="cancelEdit">取消</el-button>
              </el-form-item>
            </el-form>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    data() {
      return {
        form: {
          name: '',
          description: '',
          discount_price: '',
          original_price: '',
          stock: 0,
          category_id: 0,
          is_onsale: '',
          is_new: '',
          is_hot: '',
          is_agent_type: '',
          agent_type_id: '',
          delivery_fee: '',
          sort: '',
          attribute_id: '',
          is_coupon: '',
					update_time: '',
					thumbnail_img: '',
					updated_at: '',
					uid: '',
					already_sold: '',
					banner_img: '',
					combos_id: '',
        },
        attr_list: [],
        agent_type_list: [],
        cat_list: [],
				option_list: [
          {
          	id: 1,
            name: "是"
          },
          {
						id: 0,
						name: "否"
          }
        ]
      }
    },
    methods: {
      onSubmit() {
        console.log('submit!');
        let that = this
        axios.post('/api/good/update', this.form).then(function (response) {
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
      },
			cancelEdit () {
				this.$router.push('/good/good-list')
			}
    },

    created () {
      let that = this
      axios.get('/api/good/getAttr').then(function (response) {
        let res = response.data
        if (res.status) {
          that.cat_list = res.data.cat_list
          that.attr_list = res.data.attr_list
          that.agent_type_list = res.data.agent_list
        }
      }).catch((err) => {
        let res = err.response.data
        if (res.message == "Unauthenticated.") {
          // this.$router.push({path:'/login'})
          window.location.href = '/login'
        }
        console.log(err.response.data);
      });
      let good_id = this.$route.query.good_id
			axios.get('/api/good/edit?good_id=' + good_id,).then(function (response) {
				let res = response.data
        console.log("res",res.data)
				that.form = res.data
				console.log("res",res.data)

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

<style scoped>
  .good {
    max-width: 980px;
    margin: auto;
  }
</style>
