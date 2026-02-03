<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EdgeShellCommandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commands = [
            // System Commands
            ['label' => 'Check Disk Space', 'command' => 'df -h', 'category' => 'system', 'description' => 'Display disk usage in human-readable format', 'is_dangerous' => false],
            ['label' => 'View Memory Usage', 'command' => 'free -h', 'category' => 'system', 'description' => 'Show RAM usage and availability', 'is_dangerous' => false],
            ['label' => 'CPU Information', 'command' => 'lscpu | head -20', 'category' => 'system', 'description' => 'Display CPU architecture information', 'is_dangerous' => false],
            ['label' => 'System Uptime', 'command' => 'uptime', 'category' => 'system', 'description' => 'Show how long the system has been running', 'is_dangerous' => false],
            ['label' => 'List USB Devices', 'command' => 'lsusb', 'category' => 'system', 'description' => 'List connected USB devices', 'is_dangerous' => false],
            
            // Logs Commands
            ['label' => 'View System Logs', 'command' => 'tail -n 50 /var/log/syslog', 'category' => 'logs', 'description' => 'Show last 50 lines of system log', 'is_dangerous' => false],
            ['label' => 'View MyRVM Logs', 'command' => 'journalctl -u myrvm-edge -n 50', 'category' => 'logs', 'description' => 'Show last 50 lines of MyRVM Edge service log', 'is_dangerous' => false],
            ['label' => 'View Kernel Messages', 'command' => 'dmesg | tail -30', 'category' => 'logs', 'description' => 'Show recent kernel ring buffer messages', 'is_dangerous' => false],
            
            // Network Commands
            ['label' => 'Check Network Interfaces', 'command' => 'ip addr', 'category' => 'network', 'description' => 'List all network interfaces and their addresses', 'is_dangerous' => false],
            ['label' => 'Ping MyRVM Server', 'command' => 'ping -c 3 myrvm.orb.local', 'category' => 'network', 'description' => 'Test connectivity to MyRVM server', 'is_dangerous' => false],
            ['label' => 'Show Routing Table', 'command' => 'ip route', 'category' => 'network', 'description' => 'Display system routing table', 'is_dangerous' => false],
            ['label' => 'Active Connections', 'command' => 'ss -tuln', 'category' => 'network', 'description' => 'Show active TCP/UDP connections', 'is_dangerous' => false],
            
            // Service Commands
            ['label' => 'List Running Services', 'command' => 'systemctl list-units --type=service --state=running', 'category' => 'service', 'description' => 'List all currently running systemd services', 'is_dangerous' => false],
            ['label' => 'Check MyRVM Status', 'command' => 'systemctl status myrvm-edge', 'category' => 'service', 'description' => 'Show the status of MyRVM Edge service', 'is_dangerous' => false],
            ['label' => 'Restart MyRVM Edge', 'command' => 'sudo systemctl restart myrvm-edge', 'category' => 'service', 'description' => 'Restart the MyRVM Edge service', 'is_dangerous' => true],
            ['label' => 'Reboot Device', 'command' => 'sudo reboot', 'category' => 'service', 'description' => 'Reboot the entire edge device', 'is_dangerous' => true],
        ];

        foreach ($commands as $command) {
            DB::table('edge_shell_commands')->updateOrInsert(
                ['command' => $command['command']],
                array_merge($command, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
