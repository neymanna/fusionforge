/* soapClient.c
   Generated by gSOAP 2.3 rev 1 from Gforge.h
   Copyright (C) 2001-2003 Genivia inc.
   All Rights Reserved.
*/
#include "soapH.h"
#ifdef __cplusplus
extern "C" {
#endif

SOAP_BEGIN_NAMESPACE(soap)

SOAP_SOURCE_STAMP("@(#) soapClient.c ver 2.3 rev 1 2003-07-20 11:46:21 GMT")


SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__user(struct soap *soap, const char *URL, const char *action, char *func, tns__ArrayOfstring *params, tns__userResponse *out)
{
	struct tns__user soap_tmp_tns__user;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__user.func=func;
	soap_tmp_tns__user.params=params;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__user(soap, &soap_tmp_tns__user);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__user(soap, &soap_tmp_tns__user, "tns:user", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__user(soap, &soap_tmp_tns__user, "tns:user", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:userResponse", "tns:userResponse");
	else
		soap_get_tns__userResponse(soap, out, "tns:userResponse", "tns:userResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__logout(struct soap *soap, const char *URL, const char *action, tns__logoutResponse *out)
{
	struct tns__logout soap_tmp_tns__logout;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__logout(soap, &soap_tmp_tns__logout);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__logout(soap, &soap_tmp_tns__logout, "tns:logout", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__logout(soap, &soap_tmp_tns__logout, "tns:logout", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:logoutResponse", "tns:logoutResponse");
	else
		soap_get_tns__logoutResponse(soap, out, "tns:logoutResponse", "tns:logoutResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__hello(struct soap *soap, const char *URL, const char *action, char *parm, tns__helloResponse *out)
{
	struct tns__hello soap_tmp_tns__hello;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__hello.parm=parm;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__hello(soap, &soap_tmp_tns__hello);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__hello(soap, &soap_tmp_tns__hello, "tns:hello", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__hello(soap, &soap_tmp_tns__hello, "tns:hello", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:helloResponse", "tns:helloResponse");
	else
		soap_get_tns__helloResponse(soap, out, "tns:helloResponse", "tns:helloResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__getNumberOfActiveUsers(struct soap *soap, const char *URL, const char *action, tns__getNumberOfActiveUsersResponse *out)
{
	struct tns__getNumberOfActiveUsers soap_tmp_tns__getNumberOfActiveUsers;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__getNumberOfActiveUsers(soap, &soap_tmp_tns__getNumberOfActiveUsers);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__getNumberOfActiveUsers(soap, &soap_tmp_tns__getNumberOfActiveUsers, "tns:getNumberOfActiveUsers", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__getNumberOfActiveUsers(soap, &soap_tmp_tns__getNumberOfActiveUsers, "tns:getNumberOfActiveUsers", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:getNumberOfActiveUsersResponse", "tns:getNumberOfActiveUsersResponse");
	else
		soap_get_tns__getNumberOfActiveUsersResponse(soap, out, "tns:getNumberOfActiveUsersResponse", "tns:getNumberOfActiveUsersResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__bugList(struct soap *soap, const char *URL, const char *action, char *sessionkey, char *project, tns__bugListResponse *out)
{
	struct tns__bugList soap_tmp_tns__bugList;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__bugList.sessionkey=sessionkey;
	soap_tmp_tns__bugList.project=project;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__bugList(soap, &soap_tmp_tns__bugList);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__bugList(soap, &soap_tmp_tns__bugList, "tns:bugList", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__bugList(soap, &soap_tmp_tns__bugList, "tns:bugList", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:bugListResponse", "tns:bugListResponse");
	else
		soap_get_tns__bugListResponse(soap, out, "tns:bugListResponse", "tns:bugListResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__bugUpdate(struct soap *soap, const char *URL, const char *action, char *sessionkey, char *project, char *bugid, char *comment, tns__bugUpdateResponse *out)
{
	struct tns__bugUpdate soap_tmp_tns__bugUpdate;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__bugUpdate.sessionkey=sessionkey;
	soap_tmp_tns__bugUpdate.project=project;
	soap_tmp_tns__bugUpdate.bugid=bugid;
	soap_tmp_tns__bugUpdate.comment=comment;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__bugUpdate(soap, &soap_tmp_tns__bugUpdate);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__bugUpdate(soap, &soap_tmp_tns__bugUpdate, "tns:bugUpdate", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__bugUpdate(soap, &soap_tmp_tns__bugUpdate, "tns:bugUpdate", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:bugUpdateResponse", "tns:bugUpdateResponse");
	else
		soap_get_tns__bugUpdateResponse(soap, out, "tns:bugUpdateResponse", "tns:bugUpdateResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__group(struct soap *soap, const char *URL, const char *action, char *func, tns__ArrayOfstring *params, tns__groupResponse *out)
{
	struct tns__group soap_tmp_tns__group;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__group.func=func;
	soap_tmp_tns__group.params=params;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__group(soap, &soap_tmp_tns__group);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__group(soap, &soap_tmp_tns__group, "tns:group", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__group(soap, &soap_tmp_tns__group, "tns:group", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:groupResponse", "tns:groupResponse");
	else
		soap_get_tns__groupResponse(soap, out, "tns:groupResponse", "tns:groupResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__getPublicProjectNames(struct soap *soap, const char *URL, const char *action, tns__getPublicProjectNamesResponse *out)
{
	struct tns__getPublicProjectNames soap_tmp_tns__getPublicProjectNames;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__getPublicProjectNames(soap, &soap_tmp_tns__getPublicProjectNames);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__getPublicProjectNames(soap, &soap_tmp_tns__getPublicProjectNames, "tns:getPublicProjectNames", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__getPublicProjectNames(soap, &soap_tmp_tns__getPublicProjectNames, "tns:getPublicProjectNames", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:getPublicProjectNamesResponse", "tns:getPublicProjectNamesResponse");
	else
		soap_get_tns__getPublicProjectNamesResponse(soap, out, "tns:getPublicProjectNamesResponse", "tns:getPublicProjectNamesResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__getSiteStats(struct soap *soap, const char *URL, const char *action, tns__getSiteStatsResponse *out)
{
	struct tns__getSiteStats soap_tmp_tns__getSiteStats;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__getSiteStats(soap, &soap_tmp_tns__getSiteStats);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__getSiteStats(soap, &soap_tmp_tns__getSiteStats, "tns:getSiteStats", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__getSiteStats(soap, &soap_tmp_tns__getSiteStats, "tns:getSiteStats", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:getSiteStatsResponse", "tns:getSiteStatsResponse");
	else
		soap_get_tns__getSiteStatsResponse(soap, out, "tns:getSiteStatsResponse", "tns:getSiteStatsResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__login(struct soap *soap, const char *URL, const char *action, char *userid, char *passwd, tns__loginResponse *out)
{
	struct tns__login soap_tmp_tns__login;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__login.userid=userid;
	soap_tmp_tns__login.passwd=passwd;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__login(soap, &soap_tmp_tns__login);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__login(soap, &soap_tmp_tns__login, "tns:login", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__login(soap, &soap_tmp_tns__login, "tns:login", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:loginResponse", "tns:loginResponse");
	else
		soap_get_tns__loginResponse(soap, out, "tns:loginResponse", "tns:loginResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__bugAdd(struct soap *soap, const char *URL, const char *action, char *sessionkey, char *project, char *summary, char *details, tns__bugAddResponse *out)
{
	struct tns__bugAdd soap_tmp_tns__bugAdd;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__bugAdd.sessionkey=sessionkey;
	soap_tmp_tns__bugAdd.project=project;
	soap_tmp_tns__bugAdd.summary=summary;
	soap_tmp_tns__bugAdd.details=details;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__bugAdd(soap, &soap_tmp_tns__bugAdd);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__bugAdd(soap, &soap_tmp_tns__bugAdd, "tns:bugAdd", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__bugAdd(soap, &soap_tmp_tns__bugAdd, "tns:bugAdd", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:bugAddResponse", "tns:bugAddResponse");
	else
		soap_get_tns__bugAddResponse(soap, out, "tns:bugAddResponse", "tns:bugAddResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__getNumberOfHostedProjects(struct soap *soap, const char *URL, const char *action, tns__getNumberOfHostedProjectsResponse *out)
{
	struct tns__getNumberOfHostedProjects soap_tmp_tns__getNumberOfHostedProjects;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__getNumberOfHostedProjects(soap, &soap_tmp_tns__getNumberOfHostedProjects);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__getNumberOfHostedProjects(soap, &soap_tmp_tns__getNumberOfHostedProjects, "tns:getNumberOfHostedProjects", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__getNumberOfHostedProjects(soap, &soap_tmp_tns__getNumberOfHostedProjects, "tns:getNumberOfHostedProjects", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:getNumberOfHostedProjectsResponse", "tns:getNumberOfHostedProjectsResponse");
	else
		soap_get_tns__getNumberOfHostedProjectsResponse(soap, out, "tns:getNumberOfHostedProjectsResponse", "tns:getNumberOfHostedProjectsResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_FMAC5 int SOAP_FMAC6 soap_call_tns__bugFetch(struct soap *soap, const char *URL, const char *action, char *sessionkey, char *project, char *bugid, tns__bugFetchResponse *out)
{
	struct tns__bugFetch soap_tmp_tns__bugFetch;
	if (!URL)
		URL = "http://gforge.org/soap/SoapAPI.php";
	if (!action)
		action = "http://gforge.org/soap/SoapAPI.php";
	soap_tmp_tns__bugFetch.sessionkey=sessionkey;
	soap_tmp_tns__bugFetch.project=project;
	soap_tmp_tns__bugFetch.bugid=bugid;
	soap_begin(soap);
	soap_serializeheader(soap);
	soap_serialize_tns__bugFetch(soap, &soap_tmp_tns__bugFetch);
	soap_begin_count(soap);
	if (soap->mode & SOAP_IO_LENGTH)
	{	soap_envelope_begin_out(soap);
		soap_putheader(soap);
		soap_body_begin_out(soap);
		soap_put_tns__bugFetch(soap, &soap_tmp_tns__bugFetch, "tns:bugFetch", "");
		soap_body_end_out(soap);
		soap_envelope_end_out(soap);
	}
	if (soap_connect(soap, URL, action)
	 || soap_envelope_begin_out(soap)
	 || soap_putheader(soap)
	 || soap_body_begin_out(soap)
	 || soap_put_tns__bugFetch(soap, &soap_tmp_tns__bugFetch, "tns:bugFetch", "")
	 || soap_body_end_out(soap)
	 || soap_envelope_end_out(soap)
#ifndef WITH_LEANER
	 || soap_putattachments(soap)
#endif
	 || soap_end_send(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_default(soap);
	if (soap_begin_recv(soap)
	 || soap_envelope_begin_in(soap)
	 || soap_recv_header(soap)
	 || soap_body_begin_in(soap))
		return soap_closesock(soap);
	if (out)
		out->soap_get(soap, "tns:bugFetchResponse", "tns:bugFetchResponse");
	else
		soap_get_tns__bugFetchResponse(soap, out, "tns:bugFetchResponse", "tns:bugFetchResponse");
	if (soap->error)
	{	if (soap->error == SOAP_TAG_MISMATCH && soap->level == 2)
			return soap_recv_fault(soap);
		return soap_closesock(soap);
	}
	if (soap_body_end_in(soap)
	 || soap_envelope_end_in(soap)
#ifndef WITH_LEANER
	 || soap_getattachments(soap)
#endif
	 || soap_end_recv(soap))
		return soap_closesock(soap);
	return soap_closesock(soap);
}

SOAP_END_NAMESPACE(soap)

#ifdef __cplusplus
}
#endif

/* end of soapClient.c */
