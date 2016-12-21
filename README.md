# Grafana module for Icinga Web 2

## General Information

Insert Grafana graphs into Icinga Web 2 to display performance metrics.

## Installation

Just extract this to your Icinga Web 2 module folder in a folder called grafana.

(Configuration -> Modules -> grafana -> enable).

## Configuration

There are various configuration settings to tweak how the module behaves.

``graphs``  
Comma-separated list of checks for which graphing is supported. You must also
manually create the graph in Grafana and specifiy the dashboard and a panel ID 
(see below) to make the graph work.  
* Example *'remote_ping4, remote_ping6, load, memory'*  
* Default *empty list*

``graphs.$check.dashboard``  
The name of the dashboard to use for the check.

``graphs.$check.panel``  
The panel ID to use for the check.

``height``  
Graph height in pixels.  
* Default *280*

``host``  
Host name of the Grafana server.  
* Default *'grafana.example.com'*

``parametrized``  
Comma-separated list of checks which are parametrized. The module expects the
check name to have the format "$check $parameter" (with a space separating the
two). The graph URL will get the query parameter "var-$check=$parameter", which 
works with Grafana's template feature.  
* Example *'disk, http'*  
* Default *empty list*

``password``  
The HTTP Basic Auth password used to access Grafana. You must also specify
a username.
* Default *empty string*

``protocol``  
Protocol used to access Grafana.  
* Default *'https'*

``username``  
The HTTP Basic Auth user name used to access Grafana.
* Default *empty string*

``width``  
Graph width in pixels.  
* Default *640*

## Example configuration

This is a complete example configuration, to be put in ``/etc/icingaweb2/modules/grafana/config.ini``.

```
[grafana]
username = "admin"
password = "..."
host = "grafana.example.com"
graphs = "remote_ping4, remote_ping6, load, memory, procs, users, disk, mailq"
graphs.remote_ping4.dashboard = "base-metrics"
graphs.remote_ping4.panel = 1
graphs.remote_ping6.dashboard = "base-metrics"
graphs.remote_ping6.panel = 1
graphs.load.dashboard = "base-metrics"
graphs.load.panel = 2
graphs.memory.dashboard = "base-metrics"
graphs.memory.panel = 3
graphs.procs.dashboard = "base-metrics"
graphs.procs.panel = 4
graphs.users.dashboard = "base-metrics"
graphs.users.panel = 5
graphs.disk.dashboard = "base-metrics"
graphs.disk.panel = 6
graphs.mailq.dashboard = "base-metrics"
graphs.mailq.panel = 9
parametrized = "disk"
```

## Hats off to

This module borrows a lot from https://github.com/Icinga/icingaweb2-module-graphite.
