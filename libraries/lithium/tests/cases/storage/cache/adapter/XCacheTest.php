<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use \lithium\storage\cache\adapter\XCache;

class XCacheTest extends \lithium\test\Unit {

	/**
	 * Skip the test if XCache extension is unavailable.
	 *
	 * @return void
	 */
	public function skip() {
		$extensionExists = (extension_loaded('xcache') && (ini_get('xcache.var_size') !== 0));
		$message = 'The XCache extension is not installed or not configured for userspace caching.';
		$this->skipIf(!$extensionExists, $message);
	}

	/**
	 * Clear the userspace cache
	 *
	 * @return void
	 */
	public function setUp() {
		for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
			if (xcache_clear_cache(XC_TYPE_VAR, $i) === false) {
				return false;
			}
		}
		$this->XCache = new XCache();
	}

	public function tearDown() {
		unset($this->XCache);
	}

	public function testEnabled() {
		$this->assertTrue($this->XCache->enabled());
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->XCache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->XCache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_get($key . '_expires');
		$this->assertEqual($time, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$closure = $this->XCache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->XCache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);


		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_get($key . '_expires');
		$this->assertEqual($time, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$result = xcache_unset($key . '_expires');
		$this->assertTrue($result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = xcache_set($key . '_expires', $time, 60);
		$this->assertTrue($result);

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->XCache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$result = xcache_unset($key . '_expires');
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$result = xcache_set($key . '_expires', $time, 60);
		$this->assertTrue($result);

		$closure = $this->XCache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$result = xcache_unset($key . '_expires');
		$this->assertTrue($result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$closure = $this->XCache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$this->assertFalse($result);

	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$result = xcache_set($key . '_expires', $time, 60);
		$this->assertTrue($result);

		$closure = $this->XCache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$this->assertTrue($result);

		$this->assertFalse(xcache_unset($key));
		$this->assertFalse(xcache_unset($key . '_expires'));
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$closure = $this->XCache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->XCache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->XCache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_get($key . '_expires');
		$this->assertEqual($time, $result);

		$closure = $this->XCache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$closure = $this->XCache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$this->assertTrue($result);

		$this->assertFalse(xcache_get($key));
		$this->assertFalse(xcache_get($key . '_expires'));
	}

	public function testExpiredRead() {
		$key = 'expiring_read_key';
		$data = 'expired data';
		$time = strtotime('+1 second');

		$result = xcache_set($key . '_expires', $time, 1);
		$this->assertTrue($result);

		$result = xcache_set($key, $data, 1);
		$this->assertTrue($result);

		sleep(2);
		$closure = $this->XCache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$this->assertFalse($result);
	}

	public function testClear() {
		$key1 = 'key_clear_1';
		$key2 = 'key_clear_2';
		$time = strtotime('+1 minute');

		$result = xcache_set($key1, 'data that will no longer exist', $time);
		$this->assertTrue($result);

		$result = xcache_set($key2, 'more dead data', $time);
		$this->assertTrue($result);

		$result = $this->XCache->clear();
		$this->assertTrue($result);

		$this->assertFalse(xcache_get($key1));
		$this->assertFalse(xcache_get($key2));
	}

	public function testDecrement() {
		$time = strtotime('+1 minute');
		$key = 'decrement';
		$value = 10;

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->XCache->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$this->assertEqual($value - 1, $result);

		$result = xcache_get($key);
		$this->assertEqual($value - 1, $result);

	}

	public function testDecrementNonIntegerValue() {
		$time = strtotime('+1 minute');
		$key = 'non_integer';
		$value = 'no';

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->XCache->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(-1, $result);

		$closure = $this->XCache->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(-2, $result);
	}

	public function testIncrement() {
		$time = strtotime('+1 minute');
		$key = 'increment';
		$value = 10;

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->XCache->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
		$this->assertEqual($value + 1, $result);

		$result = xcache_get($key);
		$this->assertEqual($value + 1, $result);
	}

	public function testIncrementNonIntegerValue() {
		$time = strtotime('+1 minute');
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->XCache->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(1, $result);

		$closure = $this->XCache->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(2, $result);
	}
}

?>