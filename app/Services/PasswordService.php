<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\User;

class PasswordService
{
  public function verify(User $user,  string $plainPassword)
  {

     $config = config('const.consts.PASSWORD');

      $decrypted = openssl_decrypt(
          $user->password,
          'aes-256-cbc',
          $config['key'],
          0,
          $config['iv']
      );

      return hash_equals($decrypted, $plainPassword);
  }

}
