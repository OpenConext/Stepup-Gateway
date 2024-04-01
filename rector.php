<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassLike\RemoveAnnotationRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php80\Rector\Identical\StrStartsWithRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php80\Rector\Property\NestedAnnotationToAttributeRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ActionSuffixRemoverRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        //        __DIR__ . '/tests',
    ])
    ->withSets([
        SetList::TYPE_DECLARATION,
        SetList::PHP_82,
        SetList::CODE_QUALITY,
//        SymfonySetList::SYMFONY_64,
//        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
//        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,

    ])
    // uncomment to reach your current PHP version
     ->withPhpSets(
     )
        ->withPreparedSets(
            //            codeQuality: true
        )
    ->withRules([
            AddVoidReturnTypeWhereNoReturnRector::class,
            AddFunctionVoidReturnTypeWhereNoReturnRector::class,
            ClassPropertyAssignToConstructorPromotionRector::class,
            //        AnnotationToAttributeRector::class,
            NestedAnnotationToAttributeRector::class,
            MixedTypeRector::class,
            ChangeSwitchToMatchRector::class,
            ActionSuffixRemoverRector::class,
            StrContainsRector::class,
            StrStartsWithRector::class,
            AddPropertyTypeDeclarationRector::class,
            AddReturnTypeDeclarationRector::class,
            //        RemoveAnnotationRector::class,
            ReturnTypeFromStrictNativeCallRector::class,
            ReturnTypeFromStrictScalarReturnExprRector::class,
        ]);
