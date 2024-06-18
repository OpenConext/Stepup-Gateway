<?php

/**
 * Copyright 2017 SURFnet bv
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

use Surfnet\StepupGateway\GatewayBundle\Command\ChooseSecondFactorCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChooseSecondFactorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ChooseSecondFactorCommand $data */
        $data = $builder->getData();

        foreach ($data->secondFactors as $secondFactor) {
            $type = $secondFactor->secondFactorType;
            $builder->add('choose_' . $type, SubmitType::class, [
                'label' => 'gateway.second_factor.choose_second_factor.select',
                'attr' => [
                    'class' => 'btn btn-primary',
                    'value' => $secondFactor->secondFactorType
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChooseSecondFactorCommand::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'gateway_choose_second_factor';
    }
}
