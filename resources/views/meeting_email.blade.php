<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件</title>
    <style>
        * {
            padding: 0;
            margin: 0;
        }

        html {
            min-width: 1000px;
        }

        html,
        body {
            width: 100%;
            height: 100%;
        }

        .box {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .box .contain {
            padding: 0.2rem;
        }

        .box .contain .main {
            font-size: 0.3rem;
            /* width: 80%; */
            margin: 1rem;

        }

        .box .contain .main p {
            line-height: 1.5;
        }

        .img_Box {
            position: relative;
            overflow: hidden;

            /* box-shadow: 0px 0px 5px 3px #525252; */
        }



        .img_Box img {}

        .img_Box p {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            z-index: 9;
            position: absolute;
            left: 10%;
            top: 50%;
            transform: translate(0, -50%);
            text-shadow: 2px 2px 2px rgb(51 51 51)
        }

        .img_Box img {
            width: 100%;
        }

        .top-box-inner {
            position: relative;
            width: 100%;
            height: 200px;
            /* background: url("https://www.biolink.com.cn/uploadfile/202204/070d50e21458a61.jpg"); */
            /* overflow: hidden; */
            /* 定位 */

        }

        .top-box-inner p {
            font-size: 1rem;
            font-weight: bold;
            color: rgb(255, 255, 255);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            position: absolute;

        }

        .top-box-inner:after {
            /* 这个伪类的作用就是一个圆弧的背景色 */
            width: 140%;
            height: 200px;
            position: absolute;
            left: -20%;
            /* 之所以left:20%,是因为width:140%，宝贝可以是是别的值，例如width:160%，那么left:30%才能水平居中，可以发现规律的 */
            top: 0;
            z-index: -1;
            /*层叠顺序，最底层显示*/
            content: '';
            border-radius: 0 0 50% 50%;
            /*分别对应 左上 右上 右下 左下*/
            background-color: #248f83;
            /* 将这个伪类水平居中 */
        }
    </style>
    <script>
        (function flexible(window, document) {

            var docEl = document.documentElement

            var dpr = window.devicePixelRatio || 1

            // adjust body font size

            function setBodyFontSize() {

                if (document.body) {

                    document.body.style.fontSize = (12 * dpr) + 'px'

                }

                else {

                    document.addEventListener('DOMContentLoaded', setBodyFontSize)

                }

            }

            setBodyFontSize();

            // set 1rem = viewWidth / 10

            function setRemUnit() {
                let width_ = docEl.clientWidth < 1000 ? 1000 : docEl.clientWidth
                var rem = width_ / 24   //这里默认是10等分的，手动改为24，这个时候1rem=1920px (设计稿的宽为1920px)/24px=80px       (第二点的值的由来)

                docEl.style.fontSize = rem + 'px'

            }

            setRemUnit()

            // reset rem unit on page resize

            window.addEventListener('resize', setRemUnit)

            window.addEventListener('pageshow', function (e) {

                if (e.persisted) {

                    setRemUnit()

                }

            })

            // detect 0.5px supports

            if (dpr >= 2) {

                var fakeBody = document.createElement('body')

                var testElement = document.createElement('div')

                testElement.style.border = '.5px solid transparent'

                fakeBody.appendChild(testElement)

                docEl.appendChild(fakeBody)

                if (testElement.offsetHeight === 1) {

                    docEl.classList.add('hairlines')

                }

                docEl.removeChild(fakeBody)

            }

        }(window, document))

    </script>
</head>

<body>
<div class="box">
    <div class="top-box">
        <div class="top-box-inner">
            <p>BIO-CLOUD</p>
        </div>
    </div>
    <!-- <div class="img_Box">


    </div> -->
    <div class="contain">
        <div class="main">
            <p style="font-size:0.4rem;font-weight: bold;">主题: 【会议提醒】 的月度绩效沟通会议邀请</p>
            <br>
            <br>
            <p>正文：</p>
            <p> 尊敬的【 】领导，您好！</p>
            <br>




        </div>
    </div>
</div>
</body>

</html>
