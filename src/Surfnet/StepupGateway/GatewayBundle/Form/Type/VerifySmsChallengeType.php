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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VerifySmsChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('challenge', TextType::class, [
            'label'    => false,
            'required' => true,
            'attr'     => array(
                'autofocus' => true,
                'placeholder' => 'gateway.form.verify_sms_challenge.button.challenge_placeholder'
            )
        ]);
        $builder->add('verify_challenge', SubmitType::class, [
            'label' => 'gateway.form.verify_sms_challenge.button.verify_challenge',
            'attr'  => ['class' => 'btn btn-primary'],
        ]);
        $builder->add('resend_challenge', 'anchor', [
            'label' => 'gateway.form.verify_sms_challenge.button.resend_challenge',
            'attr'  => ['class' => 'btn btn-link'],
            'route' => 'gateway_verify_second_factor_sms',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'gateway_verify_sms_challenge';
    }
}
