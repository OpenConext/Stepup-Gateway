<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\GatewayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class VerifySmsChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('challenge', 'text', [
            'label'    => 'gateway.form.verify_sms_challenge.text.challenge',
            'required' => true,
            'attr'     => array(
                'autofocus' => true,
            )
        ]);
        $builder->add('verify-challenge', 'submit', [
            'label' => 'gateway.form.verify_sms_challenge.button.verify_challenge',
            'attr'  => ['class' => 'btn btn-primary pull-right'],
        ]);
        $builder->add('resend-challenge', 'anchor', [
            'label' => 'gateway.form.verify_sms_challenge.button.resend_challenge',
            'attr'  => ['class' => 'btn btn-default'],
            'route' => 'gateway_verify_second_factor_sms',
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Surfnet\StepupGateway\GatewayBundle\Command\VerifySmsChallengeCommand',
        ]);
    }

    public function getName()
    {
        return 'gateway_verify_sms_challenge';
    }
}
