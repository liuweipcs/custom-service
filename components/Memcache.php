<?php
/**
 * @desc Memcache缓存类
 * @author Fun
 */
namespace app\components;
use yii\caching\Dependency;
use yii\helpers\StringHelper;
class Memcache extends \yii\caching\MemCache
{
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\caching\Cache::set()
     */
    public function set($key, $value, $namespace = null, $duration = null, $dependency = null)
    {
        if ($duration === null) {
            $duration = $this->defaultDuration;
        }

        if ($dependency !== null && $this->serializer !== false) {
            $dependency->evaluateDependency($this);
        }
        if ($this->serializer === null) {
            $value = serialize([$value, $dependency]);
        } elseif ($this->serializer !== false) {
            $value = call_user_func($this->serializer[0], [$value, $dependency]);
        }

        $key = $this->buildKey($key, $namespace);
        return $this->setValue($key, $value, $duration);
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\caching\Cache::add()
     */
    public function add($key, $value, $namespace = null, $duration = 0, $dependency = null)
    {
        if ($dependency !== null && $this->serializer !== false) {
            $dependency->evaluateDependency($this);
        }
        if ($this->serializer === null) {
            $value = serialize([$value, $dependency]);
        } elseif ($this->serializer !== false) {
            $value = call_user_func($this->serializer[0], [$value, $dependency]);
        }
        $key = $this->buildKey($key, $namespace);

        return $this->addValue($key, $value, $duration);
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\caching\Cache::get()
     */
    public function get($key, $namespace = null)
    {
        $key = $this->buildKey($key, $namespace);
        $value = $this->getValue($key);
        if ($value === false || $this->serializer === false) {
            return $value;
        } elseif ($this->serializer === null) {
            $value = unserialize($value);
        } else {
            $value = call_user_func($this->serializer[1], $value);
        }
        if (is_array($value) && !($value[1] instanceof Dependency && $value[1]->isChanged($this))) {
            return $value[0];
        } else {
            return false;
        }
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\caching\Cache::buildKey()
     */
    public function buildKey($key, $namespace = null)
    {
        $namespaceKey = '';
        if (!empty($namespace))
        {
            $namespaceKey = $this->getValue($namespace);
            if ($namespace == false) 
            {
                $namespaceKey = time();
                $this->setValue($namespace, $namespaceKey, 0);
            }
        }
        if (is_string($key)) {
            $key = !empty($namespaceKey) ? $key . '_' . $namespaceKey : $key;
            $key = ctype_alnum($key) && StringHelper::byteLength($key) <= 32 ? $key : md5($key);
        } else {
            $key = !empty($namespaceKey) ? $key . '_' . $namespaceKey : $key;
            $key = md5(json_encode($key));
        }
    
        return $this->keyPrefix . $key;
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\caching\Cache::exists()
     */
    public function exists($key, $namespace = null)
    {
        $key = $this->buildKey($key, $namespace);
        $value = $this->getValue($key);
    
        return $value !== false;
    }
    
    /**
     * Deletes a value with the specified key from cache
     * @param mixed $key a key identifying the value to be deleted from cache. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @return bool if no error happens during deletion
     */
    public function delete($key, $namespace = null)
    {
        $key = $this->buildKey($key, $namespace);
    
        return $this->deleteValue($key);
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\caching\Cache::flush()
     */
    public function flush($namespace = null)
    {
        if (!empty($namespace))
        {
            $this->setValue($namespace, time(), 0);
            return true;
        }
        return $this->flushValues();
    }
}