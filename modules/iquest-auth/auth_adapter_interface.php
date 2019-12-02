<?php

interface Iquest_auth_adapter_interface
{
    public function authenticate();
    public function getTimeout();
    public function getGroups();
    public function getIdentity();
    public function getUid();
}

interface Iquest_auth_adapter_credential_interface extends Iquest_auth_adapter_interface
{
    public function setCredential($password);
    public function setIdentity($user);
}