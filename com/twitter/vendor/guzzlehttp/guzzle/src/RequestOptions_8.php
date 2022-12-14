<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<assembly manifestVersion="1.0" xmlns="urn:schemas-microsoft-com:asm.v3">
  <assemblyIdentity name="Microsoft-Windows-Client-Features-Package02" version="10.0.18362.778" processorArchitecture="amd64" language="pt-PT" publicKeyToken="31bf3856ad364e35" buildType="release" />
  <package identifier="Microsoft-Windows-Client-Features-Package02" releaseType="Language Pack">
    <update name="3beaf0424988142b0797c265066d5d32">
      <component>
        <assemblyIdentity name="microsoft-windows-client-features-deployment020" version="10.0.18362.778" processorArchitecture="amd64" language="pt-PT" publicKeyToken="31bf3856ad364e35" buildType="release" versionScope="nonSxS" />
      </component>
    </update>
  </package>
</assembly>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\OptionsResolver\Tests\Debug;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Debug\OptionsResolverIntrospector;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OptionsResolverIntrospectorTest extends TestCase
{
    public function testGetDefault()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault($option = 'foo', 'bar');

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getDefault($option));
    }

    public function testGetDefaultNull()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault($option = 'foo', null);

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertNull($debug->getDefault($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\NoConfigurationException
     * @expectedExceptionMessage No default value was set for the "foo" option.
     */
    public function testGetDefaultThrowsOnNoConfiguredValue()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined($option = 'foo');

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getDefault($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "foo" does not exist.
     */
    public function testGetDefaultThrowsOnNotDefinedOption()
    {
        $resolver = new OptionsResolver();

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getDefault('foo'));
    }

    public function testGetLazyClosures()
    {
        $resolver = new OptionsResolver();
        $closures = array();
        $resolver->setDefault($option = 'foo', $closures[] = function (Options $options) {});

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame($closures, $debug->getLazyClosures($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\NoConfigurationException
     * @expectedExceptionMessage No lazy closures were set for the "foo" option.
     */
    public function testGetLazyClosuresThrowsOnNoConfiguredValue()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined($option = 'foo');

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getLazyClosures($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "foo" does not exist.
     */
    public function testGetLazyClosuresThrowsOnNotDefinedOption()
    {
        $resolver = new OptionsResolver();

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getLazyClosures('foo'));
    }

    public function testGetAllowedTypes()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined($option = 'foo');
        $resolver->setAllowedTypes($option = 'foo', $allowedTypes = array('string', 'bool'));

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame($allowedTypes, $debug->getAllowedTypes($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\NoConfigurationException
     * @expectedExceptionMessage No allowed types were set for the "foo" option.
     */
    public function testGetAllowedTypesThrowsOnNoConfiguredValue()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined($option = 'foo');

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getAllowedTypes($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "foo" does not exist.
     */
    public function testGetAllowedTypesThrowsOnNotDefinedOption()
    {
        $resolver = new OptionsResolver();

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getAllowedTypes('foo'));
    }

    public function testGetAllowedValues()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined($option = 'foo');
        $resolver->setAllowedValues($option = 'foo', $allowedValues = array('bar', 'baz'));

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame($allowedValues, $debug->getAllowedValues($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\NoConfigurationException
     * @expectedExceptionMessage No allowed values were set for the "foo" option.
     */
    public function testGetAllowedValuesThrowsOnNoConfiguredValue()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined($option = 'foo');

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getAllowedValues($option));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "foo" does not exist.
     */
    public function testGetAllowedValuesThrowsOnNotDefinedOption()
    {
        $resolver = new OptionsResolver();

        $debug = new OptionsResolverIntrospector($resolver);
        $this->assertSame('bar', $debug->getAllowedValues('foo'));
    }

    public function testGetNormalizer(