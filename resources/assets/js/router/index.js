import Vue from 'vue';
import VueRouter from 'vue-router';
import GoodCreate from '../components/Good/Create.vue'
import LessonCreate from '../components/Lesson/Create.vue'
import BnnerUpload from '../components/Banner/Upload.vue'
import ClientList from '../components/Client/List.vue'
import BannerList from '../components/Banner/List.vue'
import Hello from '../components/Hello.vue'
import WithDrawList from '../components/WithDraw/RecordList.vue'
import SpreadList from '../components/Spread/List.vue'
import ReturnList from '../components/Order/ReturnList.vue'
import GoodList from '../components/Good/List.vue'
import DetailList from '../components/Good/DetailList.vue'
import GoodBannerList from '../components/Good/BannerList.vue'

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
			path: '/good/create',
			component: GoodCreate
		},
		{
			name: 'lesson-create',
			path: '/lesson/create',
			component: LessonCreate
		},
		{
			name: 'client-list',
			path: '/client/list',
			component: ClientList
		},
		{
			name: 'banner-list',
			path: '/banner/list',
			component: BannerList
		},
		{
			name: 'banner-upload',
			path: '/banner/upload',
			component: BnnerUpload
		},
		{
			name: 'withdraw-list',
			path: '/withdraw/list',
			component: WithDrawList
    },
    {
			name: 'return-list',
			path: '/order/return-list',
			component: ReturnList
    },
    {
      name: 'spread-list',
      path: '/spread/spread-list',
      component: SpreadList
    },
    {
      name: 'good-list',
      path: '/good/good-list',
      component: GoodList
    },
    {
      name: 'good-img',
      path: '/good/imgs',
      component: DetailList
    },
    {
      name: 'good-img',
      path: '/good/banners',
      component: GoodBannerList
    }
	]
});