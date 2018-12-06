<template>
  <div>
    <div class="card-body">
      <a href="/login">
        <h1>她芬商城后台管理系统</h1>
      </a>
    </div>
    <router-view></router-view> <!-- 路由引入的组件将在这里被渲染 -->
  </div>
</template>

<script>
	export default {
		data() {
			return {
				msg: 'Vue'
			}
		}
	}
</script>

<style>
  html, body {
    background-color: #fff;
    color: #636b6f;
    font-family: 'Nunito', sans-serif;
    font-weight: 200;
    height: 100vh;
    margin: 0;
  }
  a{
    text-decoration:none;
    color:#636b6f;
  }
  .card-body h1{
    position: absolute;
    top:50%;
    left:50%;
    width:100%;
    transform:translate(-50%,-50%);
    text-align: center;
  }

  .full-height {
    height: 100vh;
  }

  .flex-center {
    align-items: center;
    display: flex;
    justify-content: center;
  }

  .position-ref {
    position: relative;
  }

  .top-right {
    position: absolute;
    right: 10px;
    top: 18px;
  }

  .content {
    text-align: center;
  }

  .title {
    font-size: 84px;
  }

  .links > a {
    color: #636b6f;
    padding: 0 25px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .1rem;
    text-decoration: none;
    text-transform: uppercase;
  }

  .m-b-md {
    margin-bottom: 30px;
  }
</style>