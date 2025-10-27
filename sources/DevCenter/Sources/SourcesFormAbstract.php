<?php

/**
 * @brief       SourcesFormAbstract Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.1.0
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Sources;


abstract class SourcesFormAbstract
{
    /**
     * add check interfaces for nodes on the Sources tab form
     *
     * @param array $interfaces
     */
    public function formNodeInterfaces(&$interfaces)
    {
        //example
        //$interfaces[] = \IPS\myapp\MyInterface::class
    }

    /**
     * add checkboxes for the Items Interfaces on the sources tab form
     *
     * @param array $interfaces
     */
    public function formItemsInterface(&$interfaces)
    {
        //example
        //$interfaces[] = \IPS\myapp\MyInterface::class
    }

    /**
     * add checkbox for the Comment/Review Interfaces on the sources tab form
     *
     * @param array $interfaces
     */
    public function formCommentInterfaces(&$interfaces)
    {
        //example
        //$interfaces[] = \IPS\myapp\MyInterface::class
    }

    /**
     * add checkbox for the Node Traits on the sources tab form
     *
     * @param array $traits
     */
    public function formNodeTraits(&$traits)
    {
        //example
        //$traits[] = \IPS\myapp\MyTraits::class
    }

    /**
     * add checkbox for the Items Traits on the sources tab form
     *
     * @param array $traits
     */
    public function formItemsTraits(&$traits)
    {
        //example
        //$traits[] = \IPS\myapp\MyTraits::class
    }

    /**
     * add class types to the dropdownlist
     * if you add a new classtype, make sure you use a FQN here to point to its compiler
     *
     * @param $classTypes
     */
    public function formClassTypes(&$classTypes)
    {
        //example
        //$classTypes['key'] = 'value';
    }

    /**
     * add toggles to new class types
     */
    public function formClassTypeToggles($toggles)
    {
        //the forms use dtbase/sources/Forms/Forms.php, it is a wrapper class for IPS\Helper\Forms, so it has
        //a different syntax, it uses arrays, and adds a prefix to form names, so all you have to do here is
        //enter a key from above, and tell it what elements to display
        //example
        //$toggles['items'] = [
        //                'node',
        //                'namespace',
        //                'className',
        //                'implements',
        //                'item_node_class',
        //                'database',
        //                'prefix',
        //                'traits',
        //                'review_class',
        //                'comment_class',
        //                'interface_implements_item',
        //                'ips_traits_item',
        //                'scaffolding_create'
        //            ];
    }

    /**
     * adds elements to the sources form
     * The forms use dtbase/sources/Forms/Forms.php, it is a wrapper class for IPS\Helper\Forms, so it has a different
     * syntax. it uses arrays to build the form, but you can also pass it a \IPS\Helper\Forms.
     * example:
     * $elements[] = [ 'class' => 'yesno', 'name' => 'myfield', 'label' => 'My Field'(this is optional)]; this will add
     * a yesno form helper, with the name of storm_devcenter_myfield, its label will be "My Field' (that is option,
     * as you can define a language string storm_devcenter_myfield and it will us it).
     *
     * @param $elements
     */
    public function formElements(&$elements)
    {
    }

    /**
     * form data so you can alter, before it hits the compiler
     *
     * @param $values
     */
    public function formProcess(&$values)
    {
    }
}
