<?php

$redirect_url = "authenticate.php?logout=1";

if (!empty($_GET['redirect_uri']))  $redirect_url .= "&redirect_uri=".RawURLEncode($_GET['redirect_uri']);
if (!empty($_GET['logout_reason'])) $redirect_url .= "&logout_reason=".RawURLEncode($_GET['logout_reason']);

header("Location: $redirect_url");
