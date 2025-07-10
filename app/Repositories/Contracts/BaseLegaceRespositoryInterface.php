<?php

namespace App\Repositories\Contracts;

interface BaseLegaceRespositoryInterface
{
  public function getContactsAll();

  public function getContacts($id_mailing);

  public function getCommentsMailing($id_mailing);
}
