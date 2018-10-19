import Vue from 'vue';
import VueRouter from 'vue-router';
import GoodCreate from '../components/Good/Create.vue'
import Hello from '../components/Hello.vue'
Vue.use(VueRouter);

export default new VueRouter({
  saveScrollPosition: true,
  routes: [
    {
      name: 'hello',
      path: '/hello',
      component: Hello
    },
    {
      name: 'create',
      path: '/create',
      component: GoodCreate
    }
  ]
});