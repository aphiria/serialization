<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2018 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */

namespace Opulence\Serialization\Tests\Encoding;

use InvalidArgumentException;
use Opulence\Serialization\Encoding\EncoderRegistry;
use Opulence\Serialization\Encoding\EncodingException;
use Opulence\Serialization\Encoding\IEncoder;
use Opulence\Serialization\Encoding\IPropertyNameFormatter;
use Opulence\Serialization\Encoding\ObjectEncoder;
use Opulence\Serialization\Encoding\ScalarEncoder;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithArrayParams;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithNullableParams;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithTypedParamAndPublicProperty;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithTypedParams;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithTypedParamsAndNoGetters;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithTypedVariadicParams;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithUntypedOptionalParams;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithUntypedPararmsWithTypedGetters;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithUntypedScalars;
use Opulence\Serialization\Tests\Encoding\Mocks\ConstructorWithUntypedVariadicParams;
use Opulence\Serialization\Tests\Encoding\Mocks\DerivedClassWithProperties;
use Opulence\Serialization\Tests\Encoding\Mocks\NoConstructor;
use Opulence\Serialization\Tests\Encoding\Mocks\User;

/**
 * Tests the object encoder
 */
class ObjectEncoderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EncoderRegistry The encoder registry */
    private $encoders;
    /** @var ObjectEncoder The object encoder */
    private $objectEncoder;

    public function setUp(): void
    {
        $this->encoders = new EncoderRegistry();
        $this->objectEncoder = new ObjectEncoder($this->encoders);
    }

    public function testDecodingClassWithArrayConstructorParamThrowsExceptionIfEncodedValueIsNotArray(): void
    {
        $this->expectException(EncodingException::class);
        $this->objectEncoder->decode(['foo' => 'bar'], ConstructorWithArrayParams::class);
    }

    public function testDecodingClassWithArrayConstructorParamWorksIfEncodedArrayContainsScalars(): void
    {
        $encodedValue = ['foo' => ['bar', 'baz']];
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('decode')
            ->with(['bar', 'baz'], 'string[]')
            ->willReturn(['bar', 'baz']);
        $this->encoders->registerEncoder('array', $encoder);
        $value = $this->objectEncoder->decode($encodedValue, ConstructorWithArrayParams::class);
        $this->assertInstanceOf(ConstructorWithArrayParams::class, $value);
        $this->assertEquals(['bar', 'baz'], $value->getFoo());
    }

    public function testDecodingClassWithNoConstructorStillCreatesInstance(): void
    {
        $value = $this->objectEncoder->decode([], NoConstructor::class);
        $this->assertInstanceOf(NoConstructor::class, $value);
    }

    public function testDecodingClassWithPublicPropertySetsPropertyAfterInstantiation(): void
    {
        $encodedValue = ['foo' => 'dave', 'bar' => 'young'];
        $encoder = $this->createMock(IEncoder::class);
        // Public properties aren't decoded, hence why we done set that up on the mock
        $encoder->expects($this->at(0))
            ->method('decode')
            ->with('young', 'string')
            ->willReturn('young');
        $this->encoders->registerEncoder('string', $encoder);
        $value = $this->objectEncoder->decode($encodedValue, ConstructorWithTypedParamAndPublicProperty::class);
        $this->assertInstanceOf(ConstructorWithTypedParamAndPublicProperty::class, $value);
        $this->assertEquals('dave', $value->foo);
        $this->assertEquals('young', $value->getBar());
    }

    public function testDecodingClassWithTypedConstructorParamsAndNoGettersDecodesByConstructorType(): void
    {
        $encodedValue = ['foo' => 'dave', 'bar' => 'young'];
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('decode')
            ->with('dave', 'string')
            ->willReturn('dave');
        $encoder->expects($this->at(1))
            ->method('decode')
            ->with('young', 'string')
            ->willReturn('young');
        $this->encoders->registerEncoder('string', $encoder);
        $value = $this->objectEncoder->decode($encodedValue, ConstructorWithTypedParamsAndNoGetters::class);
        $this->assertInstanceOf(ConstructorWithTypedParamsAndNoGetters::class, $value);
    }

    public function testDecodingClassWithTypedConstructorParamsDecodesByConstructorType(): void
    {
        $expectedUser = new User(123, 'foo@bar.com');
        $encodedValue = ['user' => ['id' => 123, 'email' => 'foo@bar.com']];
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('decode')
            ->with(['id' => 123, 'email' => 'foo@bar.com'], User::class)
            ->willReturn($expectedUser);
        $this->encoders->registerEncoder(User::class, $encoder);
        $value = $this->objectEncoder->decode($encodedValue, ConstructorWithTypedParams::class);
        $this->assertInstanceOf(ConstructorWithTypedParams::class, $value);
        $this->assertEquals($expectedUser, $value->getUser());
    }

    public function testDecodingClassWithTypedVariadicParamsDecodesByVariadicType(): void
    {
        $encodedValue = ['users' => [['id' => 123, 'foo@bar.com'], ['id' => 456, 'email' => 'bar@baz.com']]];
        $expectedUsers = [new User(123, 'foo@bar.com'), new User(456, 'bar@baz.com')];
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('decode')
            ->with($encodedValue['users'], User::class . '[]')
            ->willReturn($expectedUsers);
        $this->encoders->registerEncoder('array', $encoder);
        $value = $this->objectEncoder->decode($encodedValue, ConstructorWithTypedVariadicParams::class);
        $this->assertInstanceOf(ConstructorWithTypedVariadicParams::class, $value);
        $this->assertEquals($expectedUsers, $value->getUsers());
    }

    public function testDecodingClassWithUntypedConstructorParamsAndUntypedGettersStillWorksIfEncodedValuesAreScalars(): void
    {
        $encodedValue = ['foo' => 123, 'bar' => 456];
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('decode')
            ->with(123, 'integer')
            ->willReturn(123);
        $encoder->expects($this->at(1))
            ->method('decode')
            ->with(456, 'integer')
            ->willReturn(456);
        $this->encoders->registerEncoder('integer', $encoder);
        $value = $this->objectEncoder->decode($encodedValue, ConstructorWithUntypedScalars::class);
        $this->assertInstanceOf(ConstructorWithUntypedScalars::class, $value);
        $this->assertEquals(123, $value->getFoo());
        $this->assertEquals(456, $value->getBar());
    }

    public function testDecodingClassWithUntypedConstructorParamsUsesGetterTypes(): void
    {
        $encodedValue = ['foo' => ['id' => 123, 'email' => 'foo@bar.com'], 'bar' => true, 'baz' => true];
        $expectedUser = new User(123, 'foo@bar.com');
        $userEncoder = $this->createMock(IEncoder::class);
        $userEncoder->expects($this->at(0))
            ->method('decode')
            ->with($encodedValue['foo'], User::class)
            ->willReturn($expectedUser);
        $this->encoders->registerEncoder(User::class, $userEncoder);
        $boolEncoder = $this->createMock(IEncoder::class);
        $boolEncoder->expects($this->at(0))
            ->method('decode')
            ->with(true, 'bool')
            ->willReturn(true);
        $boolEncoder->expects($this->at(1))
            ->method('decode')
            ->with(true, 'bool')
            ->willReturn(true);
        $this->encoders->registerEncoder('bool', $boolEncoder);
        $value = $this->objectEncoder->decode(
            $encodedValue,
            ConstructorWithUntypedPararmsWithTypedGetters::class
        );
        $this->assertInstanceOf(ConstructorWithUntypedPararmsWithTypedGetters::class, $value);
        $this->assertEquals($expectedUser, $value->getFoo());
        $this->assertTrue($value->isBar());
        $this->assertTrue($value->hasBaz());
    }

    public function testDecodingClassWitVariadicConstructorParamThrowsExceptionIfEncodedValueIsNotArray(): void
    {
        $this->expectException(EncodingException::class);
        $this->objectEncoder->decode(['foo' => 'bar'], ConstructorWithUntypedVariadicParams::class);
    }

    public function testDecodingClassWithUntypedVariadicParamsDecodesByEncodedValueType(): void
    {
        $encodedValue = ['foo' => ['bar', 'baz']];
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('decode')
            ->with($encodedValue['foo'], 'string[]')
            ->willReturn($encodedValue['foo']);
        $this->encoders->registerEncoder('array', $encoder);
        $value = $this->objectEncoder->decode($encodedValue, ConstructorWithUntypedVariadicParams::class);
        $this->assertInstanceOf(ConstructorWithUntypedVariadicParams::class, $value);
        $this->assertEquals(['bar', 'baz'], $value->getFoo());
    }

    public function testDecodingHashWithMissingPropertyStillWorksIfConstructorParamIsNullable(): void
    {
        $value = $this->objectEncoder->decode([], ConstructorWithNullableParams::class);
        $this->assertInstanceOf(ConstructorWithNullableParams::class, $value);
        $this->assertNull($value->getFoo());
    }

    public function testDecodingHashMissingPropertyStillWorksIfUntypedConstructorParamIsOptional(): void
    {
        $value = $this->objectEncoder->decode([], ConstructorWithUntypedOptionalParams::class);
        $this->assertInstanceOf(ConstructorWithUntypedOptionalParams::class, $value);
        $this->assertSame(1, $value->getFoo());
    }

    public function testDecodingHashMissingRequiredPropertyThrowsException(): void
    {
        $this->expectException(EncodingException::class);
        $this->objectEncoder->decode([], ConstructorWithTypedParams::class);
    }

    public function testDecodingNonArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->objectEncoder->decode('foo', 'bar');
    }

    public function testEncodingCreatesHashFromPropertiesOfClass(): void
    {
        $value = new ConstructorWithTypedParamsAndNoGetters('dave', 'young');
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('encode')
            ->with('dave')
            ->willReturn('dave');
        $encoder->expects($this->at(1))
            ->method('encode')
            ->with('young')
            ->willReturn('young');
        $this->encoders->registerEncoder('string', $encoder);
        $this->assertEquals(['foo' => 'dave', 'bar' => 'young'], $this->objectEncoder->encode($value));
    }

    public function testEncodingDerivedClassIncludesPropertiesFromBaseClass(): void
    {
        $value = new DerivedClassWithProperties('dave', 'young');
        // Base class properties come first, which is why things are in the order they are
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('encode')
            ->with('young')
            ->willReturn('young');
        $encoder->expects($this->at(1))
            ->method('encode')
            ->with('dave')
            ->willReturn('dave');
        $this->encoders->registerEncoder('string', $encoder);
        $this->assertEquals(['bar' => 'young', 'foo' => 'dave'], $this->objectEncoder->encode($value));
    }

    public function testEncodingDoesNotIncludeIgnoredProperties(): void
    {
        $user = new User(123, 'foo@bar.com');
        $this->objectEncoder->addIgnoredProperty(User::class, 'email');
        $encoder = $this->createMock(IEncoder::class);
        $encoder->expects($this->at(0))
            ->method('encode')
            ->with(123)
            ->willReturn(123);
        $this->encoders->registerEncoder('integer', $encoder);
        $this->assertEquals(['id' => 123], $this->objectEncoder->encode($user));
    }

    public function testEncodingFormatsPropertyNameFormatterIfOneIsSpecified(): void
    {
        /** @var IPropertyNameFormatter|\PHPUnit_Framework_MockObject_MockObject $propertyNameFormatter */
        $propertyNameFormatter = $this->createMock(IPropertyNameFormatter::class);
        $objectEncoder = new ObjectEncoder($this->encoders, $propertyNameFormatter);
        $this->encoders->registerDefaultScalarEncoder(new ScalarEncoder());
        $propertyNameFormatter->expects($this->at(0))
            ->method('formatPropertyName')
            ->with('id')
            ->willReturn('_id');
        $propertyNameFormatter->expects($this->at(1))
            ->method('formatPropertyName')
            ->with('email')
            ->willReturn('_email');
        $user = new User(123, 'foo@bar.com');
        $this->assertEquals(['_id' => 123, '_email' => 'foo@bar.com'], $objectEncoder->encode($user));
    }

    public function testEncodingNonObjectThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->objectEncoder->encode([]);
    }
}