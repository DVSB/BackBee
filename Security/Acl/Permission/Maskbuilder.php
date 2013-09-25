<?php

namespace BackBuilder\Security\Acl\Permission;

use Symfony\Component\Security\Acl\Permission\MaskBuilder as sfMaskBuilder;

/**
 * This class allows to build cumulative permissions easily, or convert
 * masks to a human-readable format.
 *
 * Example usage:
 * <code>
 *       $builder = new MaskBuilder();
 *       $builder
 *           ->add('view')
 *           ->add('create')
 *           ->add('edit')
 *       ;
 *       var_dump($builder->get());        // int(7)
 *       var_dump($builder->getPattern()); // string(32) ".............................ECV"
 * </code>
 *
 * We have defined some commonly used base permissions which you can use:
 * - VIEW: the SID is allowed to view the domain object / field
 * - CREATE: the SID is allowed to create new instances of the domain object / fields
 * - EDIT: the SID is allowed to edit existing instances of the domain object / field
 * - DELETE: the SID is allowed to delete domain objects
 * - UNDELETE: the SID is allowed to recover domain objects from trash
 * - COMMIT: the SID is allowed to commit domain objects
 * - PUBLISH: the SID is allowed to publish domain objects
 * - OPERATOR: the SID is allowed to perform any action on the domain object
 *             except for granting others permissions
 * - MASTER: the SID is allowed to perform any action on the domain object,
 *           and is allowed to grant other SIDs any permission except for
 *           MASTER and OWNER permissions
 * - OWNER: the SID is owning the domain object in question and can perform any
 *          action on the domain object as well as grant any permission
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class MaskBuilder extends sfMaskBuilder
{
    const MASK_COMMIT       = 256;        // 1 << 8
    const MASK_PUBLISH      = 512;        // 1 << 9

    const CODE_COMMIT       = 'S';
    const CODE_PUBLISH      = 'P';
}
