import Vue from 'vue';
import VueRouter from 'vue-router';
import GoodCreate from '../components/Good/Create.vue'
import LessonCreate from '../components/Lesson/Create.vue'
import BnnerUpload from '../components/Banner/Upload.vue'
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
    },
    {
      name: 'lesson-create',
      path: '/lesson/create',
      component: LessonCreate
    },
    {
      name: 'banner-upload',
      path: '/banner/upload',
      component: BnnerUpload
    }
  ]
});