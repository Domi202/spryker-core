<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Auth\Communication\Form;

use SprykerFeature\Zed\Gui\Communication\Form\AbstractForm;

class LoginForm extends AbstractForm
{

    const USERNAME = 'username';
    const PASSWORD = 'password';

    /**
     * @return self
     */
    protected function buildFormFields()
    {
        return $this
            ->addText(self::USERNAME, [
                'constraints' => [
                    $this->locateConstraint()->createConstraintRequired(),
                    $this->locateConstraint()->createConstraintNotBlank(),
                ],
                'attr' => [
                    'placeholder' => 'Email Address',
                ],
            ])
            ->addPassword(self::PASSWORD, [
                'constraints' => [
                    $this->locateConstraint()->createConstraintRequired(),
                    $this->locateConstraint()->createConstraintNotBlank(),
                ],
                'attr' => [
                    'placeholder' => 'Password',
                ],
            ])
        ;
    }

}
