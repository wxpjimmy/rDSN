# pragma once
# include "nfs_client.h"
# include <queue>
# include <dsn/service_api.h>
# include <dsn/internal/nfs.h>

namespace dsn {
	namespace service { 
class nfs_client_impl
	: public ::dsn::service::nfs_client
{
public:
	nfs_client_impl(const ::dsn::end_point& server, configuration_ptr config) : nfs_client(server) 
	{ 
		_server = server; 
		_client_request_count = 0;
		max_buf_size = config->get_value<uint32_t>("nfs", "max_buf_size", max_buf_size);
		max_request_count = config->get_value<uint32_t>("nfs", "max_request_count", max_request_count); // get para values from configuration file
	}
	nfs_client_impl() { _server = ::dsn::end_point::INVALID; }
	virtual ~nfs_client_impl() {}

	void begin_remote_copy(std::shared_ptr<remote_copy_request>& rci, aio_task_ptr nfs_task); // copy file request entry

	void end_copy(
		::dsn::error_code err,
		const copy_response& resp,
		void* context); // rewrite end_copy function

	void end_get_file_size(
		::dsn::error_code err,
		const ::dsn::service::get_file_size_response& resp,
		void* context); // rewrite end_get_file_size function

	void internal_write_callback(error_code err, int sz, copy_request reqc) // callback function of end_copy
	{
		if (err != ::dsn::ERR_SUCCESS) // return err when getting an error
		{
			derror("write file error\n");
			reqc.nfs_task->enqueue(err, sz, reqc.nfs_task->node());
		}
		if (reqc.isLast) // return err when it is the last request of one copy request
		{
			reqc.nfs_task->enqueue(err, sz, reqc.nfs_task->node());
		}
		return;
	}

private:
	::dsn::end_point _server;
	zlock _req_copy_file_queue_lock;
	uint32_t max_buf_size; // max size of rpc message
	uint32_t max_request_count; // max count of concurrent requests
	int _client_request_count; // concurrent request count
	std::queue<copy_request*> _req_copy_file_queue; // used to store the blocked requests
};

} } 