# Trimming PHP-FPM / Horizon worker counts (prod memory pressure)

Context: `free -m` showed ~1.5GB of 4GB swap in use on the 2GB Forge box.
`ps aux --sort=-%mem` showed no single leak — memory pressure comes from the
sum of MySQL + multiple PHP-FPM pools (shared across sites) + two Node SSR
apps + several Horizon queue workers, all resident at once. These steps
free up RAM without the cost/complexity of moving MySQL to its own server.

Run these over SSH on the Forge server (not from a local shell).

## 1. Baseline current counts

```bash
ps aux | grep -c '[p]hp-fpm: pool'
ps aux | grep -c '[h]orizon:work'
```

## 2. Trim PHP-FPM pools

Each site on Forge gets its own pool file:

```bash
sudo ls /etc/php/8.4/fpm/pool.d/
sudo cat /etc/php/8.4/fpm/pool.d/<site>.conf | grep '^pm'
```

Typical defaults:

```
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

Two options, mildest to most aggressive:

- Lower `pm.start_servers` / `pm.min_spare_servers` to 1, and `pm.max_children`
  to 2-3 — fewer idle workers resident at all times, still handles bursts.
- Switch `pm = dynamic` → `pm = ondemand` for low-traffic sites — workers spin
  down to zero and only spawn on request, trading a small cold-start latency
  for freeing that ~75-80MB per idle worker entirely between requests.

Edit directly (or via Forge dashboard: Site → PHP → pool settings, if exposed
on your plan), then reload:

```bash
sudo service php8.4-fpm reload
```

## 3. Consolidate Horizon supervisors

Open `config/horizon.php`, under `environments.production`. If separate
supervisors are each dedicated to one queue (e.g. `analysis`, `mail`) with
their own `maxProcesses`, each fixed process is a full booted Laravel worker
sitting resident regardless of load. Merge into fewer supervisors covering
multiple queues by priority instead:

```php
'supervisor-1' => [
    'connection' => 'redis',
    'queue' => ['mail', 'analysis'],   // priority order
    'balance' => 'auto',
    'minProcesses' => 1,
    'maxProcesses' => 2,
],
```

`balance => auto` still scales up under load, just from a smaller resting
baseline. Restart with:

```bash
php artisan horizon:terminate
```

(Forge's daemon supervisor brings Horizon back up with the new config — this
drains current jobs first, doesn't kill them mid-flight.)

## 4. Verify

```bash
free -m
ps aux --sort=-%mem | head
```

Recheck after a few hours of traffic to confirm swap isn't creeping back up.

## Bonus: kill the idle dev daemon

The Warp remote-server-daemon (~75MB) is a dev-session helper, not app infra.
If you're not actively using Warp against this box, killing it frees that RAM
immediately:

```bash
ps aux | grep warp
kill <pid>
```
