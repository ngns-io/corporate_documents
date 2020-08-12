<?php

class Activation {

  public static function activate() {

    /* create capability to manage CDOX */
    $role = get_role( 'administrator' );
    if ( ! empty( $role )) {
      $role->add_cap( 'cdox_manage' );
    }

    /* Seed document types taxonomy */
    cdox_register_corporate_document();
    cdox_create_document_types();

  }

}
