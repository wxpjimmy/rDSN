<?php
require_once($argv[1]); // type.php
require_once($argv[2]); // program.php
$file_prefix = $argv[3];
$_IDL_FORMAT = $argv[4];
?>
using System;
using System.IO;
using dsn.dev.csharp;

namespace <?=$_PROG->get_csharp_namespace()?> 
{
    // server app example
    public class <?=$_PROG->name?>ServerApp : ServiceApp
    {
        public override ErrorCode Start(string[] args)
        {
<?php foreach ($_PROG->services as $svc) { ?>
            _<?=$svc->name?>Server.OpenService(0);
<?php } ?>
            return ErrorCode.ERR_OK;
        }

        public override ErrorCode Stop(bool cleanup = false)
        {
<?php foreach ($_PROG->services as $svc) { ?>
            _<?=$svc->name?>Server.CloseService(0);
            _<?=$svc->name?>Server.Dispose();
<?php } ?>
            return ErrorCode.ERR_OK;
        }

<?php foreach ($_PROG->services as $svc) { ?>
        private <?=$svc->name?>Server _<?=$svc->name?>Server = new <?=$svc->name?>Server();
<?php } ?>
    }

    // client app example
    public class <?=$_PROG->name?>ClientApp : ServiceApp
    {
        public override ErrorCode Start(string[] args)
        {
            if (args.Length < 2)
            {
                throw new Exception("wrong usage: server-url or server-host server-port");                
            }

            if (args.Length >= 3)
            {
                _server.addr = Native.dsn_address_build(args[1], ushort.Parse(args[2]));
            }
            else
            {
                if (args[1].Contains("dsn://"))
                    _server = new RpcAddress(args[1]);
                else
                {
                    var addrs = args[1].Split(new char[] { ':'}, StringSplitOptions.RemoveEmptyEntries);
                    _server.addr = Native.dsn_address_build(addrs[0], ushort.Parse(addrs[1]));
                }
            }

<?php foreach ($_PROG->services as $svc) { ?>
            _<?=$svc->name?>Client = new <?=$svc->name?>Client(_server);
<?php } ?>
            _timer = Clientlet.CallAsync2(<?=$_PROG->name?>Helper.<?=$_PROG->get_test_task_code()?>, null, this.OnTestTimer, 0, 0, 1000);
            return ErrorCode.ERR_OK;
        }

        public override ErrorCode Stop(bool cleanup = false)
        {
            _timer.Cancel(true);
<?php foreach ($_PROG->services as $svc) { ?>
            _<?=$svc->name?>Client.Dispose();
            _<?=$svc->name?>Client = null;
<?php } ?>
            return ErrorCode.ERR_OK;
        }

        private void OnTestTimer()
        {
<?php
    foreach ($_PROG->services as $svc)
    {
        echo "            // test for service '". $svc->name ."'". PHP_EOL;
        foreach ($svc->functions as $f)
    {?>
            {
                <?=$f->get_csharp_request_type_name()?> req = new <?=$f->get_csharp_request_type_name()?>();
<?php if ($f->is_one_way()) { ?>
                _<?=$svc->name?>Client.<?=$f->name?>(req);
<?php } else { ?>
                //sync:
                <?=$f->get_csharp_return_type()?> resp;
                var err = _<?=$svc->name?>Client.<?=$f->name?>(req, out resp);
                Console.WriteLine("call <?=$f->get_rpc_code()?> end, return " + err.ToString());
                //async: 
                // TODO:
<?php } ?>           
            }
<?php }    
    }
?>
        }

        private SafeTaskHandle _timer;
        private RpcAddress  _server = new RpcAddress();
        
<?php foreach ($_PROG->services as $svc) { ?>
        private <?=$svc->name?>Client _<?=$svc->name?>Client;
<?php } ?>
    }

    /*
<?php foreach ($_PROG->services as $svc) { ?>
    class <?=$svc->name?>_perf_testClientApp :
        public ::dsn::service_app<<?=$svc->name?>_perf_testClientApp>, 
        public virtual ::dsn::service::clientlet
    {
    public:
        <?=$svc->name?>_perf_testClientApp()
        {
            _<?=$svc->name?>Client= null;
        }

        ~<?=$svc->name?>_perf_testClientApp()
        {
            stop();
        }

        virtual ErrorCode start(int argc, char** argv)
        {
            if (argc < 2)
                return ErrorCode.ERR_INVALID_PARAMETERS;

            dsn_address_build(_server.c_addr_ptr(), argv[1], (uint16_t)atoi(argv[2]));

            _<?=$svc->name?>Client= new <?=$svc->name?>_perf_testClient(_server);
            _<?=$svc->name?>Client->start_test();
            return ErrorCode.ERR_OK;
        }

        virtual void stop(bool cleanup = false)
        {
            if (_<?=$svc->name?>Client!= null)
            {
                delete _<?=$svc->name?>Client;
                _<?=$svc->name?>Client= null;
            }
        }
        
    private:
        <?=$svc->name?>_perf_testClient*_<?=$svc->name?>Client;
        RpcAddress _server;
    }
<?php } ?>
    */
} // end namespace
