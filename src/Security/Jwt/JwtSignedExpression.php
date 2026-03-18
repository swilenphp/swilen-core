<?php

namespace Swilen\Security\Jwt;

class JwtSignedExpression
{
    /**
     * The plain text token generated encoded in base64.
     *
     * @var string
     */
    public $plainTextToken;

    /**
     * @var \Swilen\Security\Jwt\Payload
     */
    public $payload;

    /**
     * @param string                         $token
     * @param \Swilen\Security\Jwt\Payload $payload
     *
     * @return void
     */
    public function __construct($token, Payload $payload)
    {
        $this->plainTextToken   = $token;
        $this->payload          = $payload;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->plainTextToken;
    }
}
