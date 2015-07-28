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
use Symfony\Component\OptionsResolver\OptionsResolver;

class SendSmsChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('send-challenge', 'submit', [
            'label' => 'gateway.form.gateway_send_sms_challenge.button.send_challenge',
            'attr' => [ 'class' => 'btn btn-primary' ],
        ]);
        $builder->add('cancel', 'submit', [
            'label' => 'gateway.form.send_sms_challenge.button.cancel',
            'attr'  => ['class' => 'btn btn-danger', 'formnovalidate' => 'formnovalidate'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand',
        ]);
    }

    public function getName()
    {
        return 'gateway_send_sms_challenge';
    }
}
