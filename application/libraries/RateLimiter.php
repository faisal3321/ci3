<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class RateLimiter 
{
    protected $CI;
    protected $maxTokens = 20; // max token in bucket can be 20
    protected $refillRate = 5; // 5 token per min refiller will refill the bucket


    public function __construct() 
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }


    // Allow request (rate-limit)
    public function allowRequest($identifier)
    {
        $now = new dateTime();
        $res = $this->CI->db->get_where('rate_limits', ['identifier' => $identifier])->row();

        if (!$res) {
            // first request
            $this->CI->db->insert('rate_limits', [
                'identifier'        => $identifier,
                'tokens'            => $this->maxTokens - 1,
                'last_refill'       => $now->format('Y-m-d H:i:s')
            ]);
        return true;
        }


        // minute sinch last refill
        $lastRefill = new dateTime($res->last_refill);
        $diffSeconds = $now->getTimestamp() - $lastRefill->getTimestamp();
        $minutes = $diffSeconds/60;
        $minutesPassed = floor($minutes);

        // new tokens
        $newTokens = min(
            $this->maxTokens,
            $res->tokens + ($minutesPassed * $this->refillRate)
        );

        if ($newTokens <= 0) {
            return false;
        }

        // deduct 1 token on each request
        $this->CI->db->where('identifier', $identifier)->update('rate_limits', [
            'tokens'        => $newTokens - 1,
            'last_refill'   => $now->format('Y-m-d H:i:s')
        ]);

        return true;
    }



    // throttle slow down the request when token is less
    public function throttle($identifier)
    {
        $res = $this->CI->db->get_where('rate_limits', ['identifier' => $identifier])->row();

        if (!$res) return ;

        if ($res->tokens < 5) {

            $delaySeconds = ($this->maxTokens - $res->tokens) * 0.2;
            $out = min(3, $delaySeconds); // max load for 3 seconds

            // usleep works in microseconds. (1 second = 1,000,000 microseconds)
            usleep($out * 1000000);  
        }

        return true;
    }
}