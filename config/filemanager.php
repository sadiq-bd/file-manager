<?php

return [

    'root_dir' => env('FILE_ROOT_DIR', '/'),

    'temp_dir' => env('FILE_TEMP_DIR', '/tmp'),

    'basic_auth_cred' => env('BASIC_AUTH_CRED', ''),

    'upload_chunk_size' => env('FILE_UPLOAD_CHUNK_SIZE', '3145728'),

    'nginx_x_sendfile_path' => env('NGINX_X_SENDFILE_PATH', ''),

    'use_shell_commands_for_file_handling' => (bool) env('USE_SHELL_COMMANDS_FOR_FILE_HANDLING', false),

    'version' => '1.2.0',

];
