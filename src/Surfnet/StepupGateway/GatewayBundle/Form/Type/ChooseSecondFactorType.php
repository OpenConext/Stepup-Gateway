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

use Surfnet\StepupGateway\GatewayBundle\Command\ChooseSecondFactorCommand;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChooseSecondFactorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ChooseSecondFactorCommand $data */
        $data = $builder->getData();
        $secondFactors = array_values($data->secondFactors->getValues());

        $builder->add('selectedSecondFactor', 'choice', [
            'label' => 'gateway.form.choose_second_factor.button.second_factor',
            'expanded' => true,
            'multiple' => false,
            'choices' => $secondFactors,
            'choice_label' => function ($index) use ($secondFactors) {
                /** @var SecondFactor[] $secondFactors */
                return ucfirst($secondFactors[$index]->secondFactorType);
            }
        ]);

        $builder->add('choose', 'submit', [
            'label' => 'gateway.form.choose_second_factor.button.submit',
            'attr' => [ 'class' => 'btn btn-primary pull-right' ],
        ]);
        $builder->add('cancel', 'submit', [
            'label' => 'gateway.form.choose_second_factor.button.cancel',
            'attr'  => ['class' => 'btn btn-danger', 'formnovalidate' => 'formnovalidate'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Surfnet\StepupGateway\GatewayBundle\Command\ChooseSecondFactorCommand',
        ]);
    }

    public function getName()
    {
        return 'gateway_choose_second_factor';
    }
}
