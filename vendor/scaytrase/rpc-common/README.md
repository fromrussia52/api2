[![Latest Stable Version](https://poser.pugx.org/scaytrase/rpc-common/v/stable)](https://packagist.org/packages/scaytrase/rpc-common) 
[![Total Downloads](https://poser.pugx.org/scaytrase/rpc-common/downloads)](https://packagist.org/packages/scaytrase/rpc-common) 
[![Latest Unstable Version](https://poser.pugx.org/scaytrase/rpc-common/v/unstable)](https://packagist.org/packages/scaytrase/rpc-common) 
[![License](https://poser.pugx.org/scaytrase/rpc-common/license)](https://packagist.org/packages/scaytrase/rpc-common)

[![Build Status](https://travis-ci.org/scaytrase/rpc-common.svg)](https://travis-ci.org/scaytrase/rpc-common?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/scaytrase/rpc-common/badges/quality-score.png)](https://scrutinizer-ci.com/g/scaytrase/rpc-common/)
[![Code Coverage](https://scrutinizer-ci.com/g/scaytrase/rpc-common/badges/coverage.png)](https://scrutinizer-ci.com/g/scaytrase/rpc-common/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/53c29a01-8a7b-48c9-bd70-badb3d23ad32/mini.png)](https://insight.sensiolabs.com/projects/53c29a01-8a7b-48c9-bd70-badb3d23ad32)

# RPC Library

Built-in support for batch-like requests. Processing depends on 
client implementation (may not be real batch)

## Common interfaces
  * RPC request (call)
  * RPC response (result)
  * RPC error
  
## Decorators
  * Lazy client decorator
  * Loggable client decorator
  * Cacheable client decorator

## Test utils
  * Mock client to create response queue with acceptance filter
