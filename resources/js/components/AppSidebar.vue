<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    ClipboardList,
    History,
    LayoutDashboard,
    ShoppingBag,
    ShoppingCart,
    Truck,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as cashier } from '@/routes/cashier';
import { index as inventory } from '@/routes/inventory/products';
import { index as purchaseHistory } from '@/routes/purchases/history';
import { index as inquiries } from '@/routes/purchases/inquiries';
import { index as orders } from '@/routes/purchases/orders';
import { index as sales } from '@/routes/sales';
import { index as suppliers } from '@/routes/suppliers';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const permissions = page.props.auth.user?.permissions ?? [];
    const items: NavItem[] = [
        {
            title: 'Panel principal',
            href: dashboard(),
            icon: LayoutDashboard,
        },
    ];

    if (permissions.includes('inventario.ver')) {
        items.push({
            title: 'Inventario',
            href: inventory(),
            icon: Boxes,
        });
    }

    if (permissions.includes('ventas.ver')) {
        items.push({
            title: 'Ventas',
            href: sales(),
            icon: ShoppingBag,
        });
    }

    if (permissions.includes('caja.ver')) {
        items.push({
            title: 'Caja',
            href: cashier(),
            icon: Wallet,
        });
    }

    if (permissions.includes('proveedores.ver')) {
        items.push({
            title: 'Proveedores',
            href: suppliers(),
            icon: Truck,
        });
    }

    if (permissions.includes('consultas-proveedor.ver')) {
        items.push({
            title: 'Consultas y cotizaciones',
            href: inquiries(),
            icon: ClipboardList,
        });
    }

    if (permissions.includes('pedidos-compra.ver')) {
        items.push({
            title: 'Pedidos a proveedores',
            href: orders(),
            icon: ShoppingCart,
        });
    }

    if (permissions.includes('historial-compras.ver')) {
        items.push({
            title: 'Historial de compras',
            href: purchaseHistory(),
            icon: History,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
