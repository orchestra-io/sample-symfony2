<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Amazon_S3, a PHP5 API for accessing the Amazon Simple Storage
 * Service, Amazon S3.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008-2009, Peytz & Co. A/S
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  * Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the distribution.
 *  * Neither the name of the PHP_LexerGenerator nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <services.amazon.s3@chsc.dk>
 * @copyright 2008-2009 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: $Id: S3.php 291670 2009-12-03 21:12:40Z gauthierm $
 * @link      http://pear.php.net/package/Services_Amazon_S3
 * @link      https://s3.amazonaws.com/
 * @link      http://docs.amazonwebservices.com/AmazonS3/2006-03-01/
 */

/**
 * Use HTTP_Request2 for connecting to the S3 REST interface.
 */
require_once 'HTTP/Request2.php';

/**
 * Use Crypt_HMAC2 for signing REST requests.
 */
require_once 'Crypt/HMAC2.php';

require_once 'Services/Amazon/S3/Resource.php';
require_once 'Services/Amazon/S3/Resource/Bucket.php';
require_once 'Services/Amazon/S3/Resource/Object.php';
require_once 'Services/Amazon/S3/Exception.php';
require_once 'Services/Amazon/S3/ServerErrorException.php';

/**
 * The main S3 class contains the credentials used for accessing the S3
 * service and is a starting point for accessing the storage service.
 *
 * Sample usage:
 * <code>
 * require_once 'Services/Amazon/S3.php';
 * // Replace with your own credentials
 * $accessKeyId = '0PN5J17HBGZHT7JJ3X82';
 * $secretAccessKey = 'uV3F3YluFJax1cknvbcGwgjvx4QpvB+leU8dUj2o';
 * $s3 = Services_Amazon_S3::getAccount($accessKeyId, $secretAccessKey);
 * </code>
 *
 * The S3 account instance can now be used to list the buckets owned by this
 * account. A bucket is a file container similar to a filesystem directory,
 * except that it cannot contain subdirectories.
 * <code>
 * foreach ($s3->getBuckets() as $bucket) {
 *     print '<li>' . htmlspecialchars($bucket->name) . '</li>';
 * }
 * </code>
 *
 * Get a specific bucket:
 * <code>
 * $bucket = $s3->getBucket('foobar');
 * </code>
 *
 * Iterate over the objects (files) in a bucket:
 * <code>
 * foreach ($bucket->getObjects() as $object) {
 *     print '<li>' . htmlspecialchars($object->key) . '</li>';
 * }
 * </code>
 *
 * Fetch a specific object:
 * <code>
 * $object = $bucket->getObject('foo.gif');
 * $object->load();
 * $img = imagecreatefromstring($object->data);
 * </code>
 *
 * Save an object with public read access:
 * <code>
 * require_once 'Services/Amazon/S3/AccessControlList.php';
 * $object = $bucket->getObject('foo.txt');
 * $object->acl = Services_Amazon_S3_AccessControlList::ACL_PUBLIC_READ;
 * $object->data = 'lorem ipsum dolor sit amet';
 * $object->save();
 * </code>
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <services.amazon.s3@chsc.dk>
 * @copyright 2008-2009 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   Release: @release-version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */
class Services_Amazon_S3
{
    // {{{ class constants

    /**
     * Namespace URI used in XML.
     */
    const NS_S3 = 'http://s3.amazonaws.com/doc/2006-03-01/';

    /**
     * Namespace URI for XML schema instances.
     */
    const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    // }}}
    // {{{ public properties

    /**
     * Amazon Web Services Access Key ID.
     * @var string|bool 20-character, alphanumeric string, or false if this is
     *                  an anonymous account
     */
    public $accessKeyId;

    /**
     * Amazon Web Services Secret Access Key.
     * @var string 40-character string
     */
    public $secretAccessKey;

    /**
     * Configuration parameters for HTTP_Request2, e.g. proxy server,
     * timeout etc. NOTE: Some options may interfere with this service.
     * @var array  configuration array for HTTP_Request2.
     * @see HTTP_Request2::HTTP_Request2()
     */
    public $httpConfig = array('follow_redirects' => true);

    /**
     * The default method for accessing buckets. This value may be specified
     * per bucket using $bucket->requestStyle.
     * @var string  a Services_Amazon_S3::REQUEST_STYLE_xxx constant
     * @see Services_Amazon_S3_Resource_Bucket::REQUEST_STYLE_VIRTUAL_HOST
     * @see Services_Amazon_S3_Resource_Bucket::REQUEST_STYLE_PATH
     * @see Services_Amazon_S3_Resource_Bucket::REQUEST_STYLE_CNAME
     * @see Services_Amazon_S3_Resource_Bucket::$requestStyle
     * @link http://docs.amazonwebservices.com/AmazonS3/2006-03-01/VirtualHosting.html
     */
    public $requestStyle
        = Services_Amazon_S3_Resource_Bucket::REQUEST_STYLE_VIRTUAL_HOST;

    /**
     * The hostname of the endpoint used for requests done by this class and
     * requests for buckets done with REQUEST_STYLE_VIRTUAL_HOST and
     * REQUEST_STYLE_PATH. This value may be specified per bucket using
     * $bucket->endpoint.
     * @see Services_Amazon_S3_Resource_Bucket::$endpoint
     * @see Services_Amazon_S3_Resource_Bucket::REQUEST_STYLE_PATH
     */
    public $endpoint = 's3.amazonaws.com';

    /**
     * Whether connections should be made using HTTPS.
     * @var bool
     */
    public $useSSL = false;

    /**
     * Maximum number of keys to fetch per request when iterating over the
     * objects of a bucket. A low value will result in more requests to the
     * serve. This value may be specified on the iterator using
     * <kbd>$iterator->maxKeys</kbd>.
     * @var int|false  a positive integer, or false to let the server decide
     *                 the limit
     * @see Services_Amazon_S3_ObjectIterator::$maxKeys
     */
    public $maxKeys = false;

    /**
     * Maximum number of retries to make if a request fails with a 500 Internal
     * server error, or if a transport-level error occurs.
     * @var int  a non-negative integer, 0 means do not retry
     */
    public $maxRetries = 2;

    // }}}
    // {{{ protected properties

    /**
     * Object used to make HTTP requests for the S3 REST API.
     *
     * @var HTTP_Request2
     *
     * @see Services_Amazon_S3::setRequest()
     */
    protected $request = null;

    // }}}
    // {{{ __construct()

    /**
     * Private constructor. Use Services_Amazon_S3::getService() or
     * Services_Amazon_S3::getAnonymousService().
     */
    private function __construct()
    {
        $this->request = new HTTP_Request2();
    }

    // }}}
    // {{{ setRequest()

    /**
     * Sets the HTTP request object to use
     *
     * Can be used to mock requests for testing.
     *
     * @param HTTP_Request2 $request the HTTP request object to use.
     *
     * @return void
     */
    public function setRequest(HTTP_Request2 $request)
    {
        $this->request = $request;
    }

    // }}}
    // {{{ getAccount()

    /**
     * Returns an account instance with the specified credentials. This may
     * be used to access resources owned by this account as well as resources
     * owned by other accounts that have permissions granted to this account
     * or to the authenticated users group.
     *
     * @param string $accessKeyId     Amazon Web Services Access Key ID
     *                                (20-character, alphanumeric string)
     * @param string $secretAccessKey Amazon Web Services Secret Access Key
     *                                (40-character string)
     *
     * @return Services_Amazon_S3
     * @see Services_Amazon_S3_AccessControlList::URI_AUTHENTICATED_USERS
     */
    public static function getAccount($accessKeyId, $secretAccessKey)
    {
        $s3                  = new Services_Amazon_S3();
        $s3->accessKeyId     = $accessKeyId;
        $s3->secretAccessKey = $secretAccessKey;
        return $s3;
    }

    // }}}
    // {{{ getAnonymousAccount()

    /**
     * Returns an unauthorized account instance. This can be used to access
     * resources that have permissions granted to anonymous users.
     *
     * @return Services_Amazon_S3
     * @see Services_Amazon_S3_AccessControlList::URI_ALL_USERS
     */
    public static function getAnonymousAccount()
    {
        $s3              = new Services_Amazon_S3();
        $s3->accessKeyId = false;
        return $s3;
    }

    // }}}
    // {{{ getURL()

    /**
     * Returns the base URL of this resource.
     *
     * @return string  an absolute URL
     */
    public function getURL()
    {
        return ($this->useSSL ? 'https' : 'http') . '://' . $this->endpoint . '/';
    }

    // }}}
    // {{{ getBuckets()

    /**
     * Returns the buckets owned by the current user.
     *
     * @return Traversable a traversable collection of
     *                     {@link Services_Amazon_S3_Resource_Bucket}
     *                     instances.
     *
     * @throws Services_Amazon_S3_Exception
     */
    public function getBuckets()
    {
        $request = $this->sendRequest($this);
        $xPath   = self::getDOMXPath($request);

        $buckets = array();
        $query   = '/s3:ListAllMyBucketsResult/s3:Buckets/s3:Bucket/s3:Name/text()';
        foreach ($xPath->evaluate($query) as $node) {
            $buckets[] = new Services_Amazon_S3_Resource_Bucket($this, $node->data);
        }
        // Specify Traversable in the documentation to allow us to change to
        // RecursiveIterator in the future without breaking the documented API
        return new ArrayObject($buckets);
    }

    // }}}
    // {{{ getBucket()

    /**
     * Returns the buckets with the specified name.
     *
     * @param string $name the bucket name (UTF-8)
     *
     * @return Services_Amazon_S3_Resource_Bucket
     */
    public function getBucket($name)
    {
        return new Services_Amazon_S3_Resource_Bucket($this, $name);
    }

    // }}}
    // {{{ getRequestSignature()

    /**
     * Signs the specified request with the secret key and returns the request
     * signature used by Services_Amazon_S3::sendRequest and
     * Services_Amazon_S3_Resource_Bucket::getSignedUrl().
     *
     * @param string $method      HTTP method - "GET", "PUT", or "DELETE"
     * @param mixed  $resource    an instance of Services_Amazon_S3 or
     *                            Services_Amazon_S3_Resource
     * @param string $subResource e.g. "?acl", "?location", or "?torrent"
     *                            (including the question mark)
     * @param array  $headers     associative array of HTTP request headers -
     *                            the "date" header is mandatory, "content-md5"
     *                            and "content-type" are optional. All keys
     *                            must be in lowercase.
     *
     * @return string
     *
     * @throws Services_Amazon_S3_Exception
     *
     * @link http://docs.amazonwebservices.com/AmazonS3/2006-03-01/RESTAuthentication.html
     */
    public function getRequestSignature(
        $method,
        $resource,
        $subResource,
        $headers
    ) {
        $stringToSign = $method . "\n" .
            (isset($headers['content-md5']) ? $headers['content-md5'] : '') .
            "\n" .
            (isset($headers['content-type']) ? $headers['content-type'] : '') .
            "\n" .
            $headers['date'] . "\n";

        // Generate CanonicalizedAmzHeaders part
        $amzHeaders = array();
        foreach ($headers as $name => $value) {
            if (strncmp($name, 'x-amz-', 6) === 0) {
                $amzHeaders[rtrim($name)] = trim($value);
            }
        }

        ksort($amzHeaders);

        foreach ($amzHeaders as $name => $value) {
            // unfold long headers
            $value = preg_replace('/\s*\n\s*/', ' ', $value);
            // add to string
            $stringToSign .= $name . ':' . $value . "\n";
        }

        // Generate CanonicalizedResource part
        if ($resource instanceof Services_Amazon_S3) {
            $stringToSign .= '/';
        } elseif ($resource instanceof Services_Amazon_S3_Resource_Bucket) {
            $stringToSign .= '/' . rawurlencode($resource->name) . '/';
        } elseif ($resource instanceof Services_Amazon_S3_Resource_Object) {
            $stringToSign .= '/' . rawurlencode($resource->bucket->name) .
                             '/' . rawurlencode($resource->key);
        }
        $stringToSign .= $subResource;

        return $this->signString($stringToSign);

    }

    // }}}
    // {{{ signString()

    /**
     * Signs the specified string with the secret key.
     *
     * @param string $stringToSign UTF-8
     *
     * @return string a 28 character string.
     *
     * @throws Services_Amazon_S3_Exception  if this is an anonymous account
     */
    public function signString($stringToSign)
    {
        if (!$this->accessKeyId) {
            throw new Services_Amazon_S3_Exception(
                'Anonymous account cannot sign strings');
        }

        // Generate signature
        $hmac = new Crypt_HMAC2($this->secretAccessKey, 'SHA1');
        $signature = $hmac->hash($stringToSign, Crypt_HMAC2::BINARY);

        // Amazon wants the signature value base64-encoded
        return base64_encode($signature);
    }

    // }}}
    // {{{ sendRequest()

    /**
     * Sends the specified request to the server. This method is for internal
     * use only.
     *
     * @param mixed  $resource    an instance of Services_Amazon_S3 or
     *                            Services_Amazon_S3_Resource
     * @param string $subResource e.g. "?acl", "?location", or "?torrent"
     *                            (including the question mark)
     * @param array  $query       additional query string parameters
     * @param string $method      HTTP method - "GET", "PUT", or "DELETE"
     * @param array  $headers     associative array of HTTP request headers,
     *                            all keys must be in lowercase
     * @param string $body        HTTP request body for PUT requests
     *
     * @return HTTP_Request2_Response
     *
     * @throws Services_Amazon_S3_Exception
     */
    public function sendRequest(
        $resource,
        $subResource = false,
        $query = null,
        $method = HTTP_Request2::METHOD_GET,
        array $headers = array(),
        $body = false
    ) {
        $headers['date'] = gmdate(DATE_RFC1123);

        // Sign request, unless this is an anonymous account
        if ($this->accessKeyId) {
            $headers['authorization'] = 'AWS ' . $this->accessKeyId . ':' .
                $this->getRequestSignature(
                    $method,
                    $resource,
                    $subResource,
                    $headers
                );

        }

        // Generate URL
        $url = $resource->getURL();
        if ($subResource) {
            $url .= $subResource;
            if ($query) {
                $url .= '&';
            }
        } elseif ($query) {
            $url .= '?';
        }
        if ($query) {
            $url .= http_build_query($query, '', '&');
        }

        // Convert null headers to empty strings so they still get sent with
        // the request. (Bug #16827)
        foreach ($headers as $name => $value) {
            if ($value === null) {
                $headers[$name] = '';
            }
        }

        // Send request
        try {
            $request = clone $this->request;

            $request->setConfig($this->httpConfig);
            $request->setUrl($url);
            $request->setMethod($method);

            if ($method === HTTP_Request2::METHOD_PUT) {
                $request->setBody($body);
            }

            $request->setHeader($headers);

            for ($i = 0; $i <= $this->maxRetries; $i++) {
                $response = $request->send();
                if ($response->getStatus() != 500) {
                    break;
                }
            }
        } catch (HTTP_Request2_Exception $e) {
            throw new Services_Amazon_S3_Exception($e->getMessage(),
                                                   $e->getCode());
        }

        if ($response->getStatus() >= 300) {
            if ($response->getStatus() == 301) {
                // Permanents redirects without a Location header indicates that
                // the wrong endpoint is being used, e.g. due to DNS problems. A
                // temporary fix is to change /etc/hosts or similar on the local
                // machine (or change this method to retry the call on the
                // specified endpoint).
                $message = $xPath->evaluate(
                    'concat(' .
                    '    string(/Error/Message),' .
                    '    " Endpoint: ",' .
                    '    string(/Error/Endpoint)' .
                    ')'
                );

                throw new Services_Amazon_S3_Exception(
                    $message ? $message : $response
                );
            } elseif ($response->getStatus() == 403
                      && $method == HTTP_Request2::METHOD_GET
            ) {
                // getDOMXPath() may throw a Services_Amazon_S3_ServerErrorException
                $xPath = self::getDOMXPath($response);
                $code  = $xPath->evaluate('string(/Error/Code)');

                // RequestTimeTooSkewed indicates that local clock is scewed.
                // SignatureDoesNotMatch indicates a bug in
                // self::getRequestSignature().
                if (   $code != 'RequestTimeTooSkewed'
                    && $code != 'SignatureDoesNotMatch'
                ) {
                    include_once 'Services/Amazon/S3/AccessDeniedException.php';
                    throw new Services_Amazon_S3_AccessDeniedException($response);
                } else {
                    throw new Services_Amazon_S3_Exception($response);
                }
            } elseif ($response->getStatus() == 404) {
                include_once 'Services/Amazon/S3/NotFoundException.php';
                throw new Services_Amazon_S3_NotFoundException($response);
            } elseif ($response->getStatus() >= 500) {
                throw new Services_Amazon_S3_ServerErrorException($response);
            } else {
                throw new Services_Amazon_S3_Exception($response);
            }
        }

        return $response;
    }

    // }}}
    // {{{ getDOMXPath()

    /**
     * Returns a DOMXPath object for the XML document in the body of the
     * specified HTTP response. This method is for internal use only.
     *
     * @param HTTP_Request2_Response $response the HTTP response.
     *
     * @return DOMXPath
     * @throws Services_Amazon_S3_ServerErrorException
     */
    public static function getDOMXPath(HTTP_Request2_Response $response)
    {
        if ($response->getHeader('content-type') != 'application/xml') {
            throw new Services_Amazon_S3_ServerErrorException(
                'Response was not of type application/xml', $response);
        }
        $prevUseInternalErrors = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $ok  = $doc->loadXML($response->getBody());
        libxml_use_internal_errors($prevUseInternalErrors);
        if (!$ok) {
            throw new Services_Amazon_S3_ServerErrorException($response);
        }

        $xPath = new DOMXPath($doc);
        $xPath->registerNamespace('s3', self::NS_S3);
        $xPath->registerNamespace('xsi', self::NS_XSI);
        return $xPath;
    }

    // }}}
}

?>
