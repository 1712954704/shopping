<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Common;


use App\Http\Controllers\BaseController;
use App\Http\Service\Common\UserService;
use library\Constants\StatusConstants;


class RabbitmqController extends BaseController
{
    /**
     * @var \AMQPConnection
     */
    private $connect;
    private $exchange_name;
    private $type;
    private $third_model;
    /**
     * @var \AMQPExchange
     */
    private $exchange;
    /**
     * @var \AMQPChannel
     */
    private $channel;
    /**
     * @var \AMQPQueue
     */
    private $queue;

    public function __construct()
    {
        $this->is_login = 0;
        parent::__construct();
    }


    /**
     * rabbitmq相关
     *
     */
    public function rabbitmq_operate()
    {
//        $user_service = new UserService();
//        $user_service->test_redis();
//        var_dump(111);die();

        try {
            switch ($this->method) {
                case 'GET':
                    break;
                case 'POST':
                    // 加载rabbitmq配置
                    $rabbitmq_ini = \Common::fromFile(CONFIG_PATH. 'rabbitmq.ini');
                    $conn_args = array(
                        'host' => $rabbitmq_ini['default']['host'],  //rabbitmq 服务器host
                        'port' => $rabbitmq_ini['default']['port'],   //rabbitmq 服务器端口
                        'login' => $rabbitmq_ini['default']['login'],     //登录用户
                        'password' => $rabbitmq_ini['default']['password'],   //登录密码
                        'vhost' => $rabbitmq_ini['default']['vhost']         //虚拟主机
//                        'host' => '192.168.182.250',  //rabbitmq 服务器host
//                        'port' => '5672',   //rabbitmq 服务器端口
//                        'login' => 'guest',     //登录用户
//                        'password' => 'Polypore23!',   //登录密码
//                        'vhost' => $rabbitmq_ini['vhost']         //虚拟主机
                    );
                    $this->connect = new \AMQPConnection($conn_args);
                    if(!$this->connect->connect()){
                        throw new \Exception("Cannot connect to the broker", 1);
                    }
//                    var_dump($this->connect);die();

                    // 创建交换机名称和类型
                    $this->channel = new \AMQPChannel($this->connect);

                    $this->exchange = new \AMQPExchange($this->channel);
                    $this->exchange_name = 'exchange'.'common_callback';
                    $this->exchange->setName($this->exchange_name);
                    $this->exchange->setType(AMQP_EX_TYPE_DIRECT);
                    $this->exchange->setFlags(AMQP_DURABLE);
                    $status = $this->exchange->declareExchange();
                    // 创建queue队列名称
                    $this->queue = new \AMQPQueue($this->channel);
                    $this->queue->setName('common_callback');
                    $this->queue->setFlags(AMQP_DURABLE);

                    $msg = json_encode(array('Hello World!','DIRECT'));
                    // 将指定队列绑定到交换机上的路由键
                    $this->queue->bind($this->exchange_name, 'common_callback');
//                    $e = $this->consume();
                     //发送消息
                    $publish_config = ['delivery_mode' => AMQP_DURABLE]; // 消息持久化
                    $e = $this->exchange->publish($msg, 'common_callback', 1, $publish_config);
                    $this->connect->disconnect();
                    var_dump($e);die();

                    // 接收消息
                    $this->queue->consume([$this,'processMessage']);
                    // 断开连接
                    $this->connect->disconnect();
                    var_dump(111);die();
                    break;
                default:
                    return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
            }
        }catch (\Exception $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());
        } finally {
            echo 111;
        }
//        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    public function processMessage($envelope, $queue) {
        $msg = $envelope->getBody();
        var_dump("Received: " . $msg);
        $queue->ack($envelope->getDeliveryTag()); // 手动发送ACK应答
    }



    public function consume(callable $callable = null, $flag = AMQP_AUTOACK){
        try{
            if ($this->queue) {
                $this->queue->declareQueue();
            }
//            $channel = new \AMQPChannel($this->connect);
//
//            $q = new \AMQPQueue($channel);
//            $q->setName($this->config[$this->type]['queue_name']);
//            $q->setFlags(AMQP_DURABLE);
//            $status = $q->declareQueue();

            if (is_null($callable)) {
                $callable = [$this, 'processMessage'];
            }

            var_dump($callable);
            var_dump($flag);
//            $q->consume($callable, $flag); //自动ACK应答
            $this->queue->consume($callable, $flag); //自动ACK应答
        }catch (\Exception $exception) {
            var_dump($exception);
        } catch(\Error $error) {
            var_dump($error);
        }finally{
        }
    }

}
