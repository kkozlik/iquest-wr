<?php

class Traccar_api_query_exception extends Exception {}
class Traccar_api_not_found_exception extends Traccar_api_query_exception {}
class Traccar_api_unauthenticated_exception extends Traccar_api_query_exception {}
