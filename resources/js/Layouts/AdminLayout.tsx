import { Link, router, usePage } from '@inertiajs/react';
import { Alert, AppBar, Avatar, Box, Button, Container, Divider, Drawer, IconButton, InputAdornment, List, ListItemButton, ListItemText, Menu, MenuItem, Stack, TextField, Toolbar, Typography } from '@mui/material';
import SearchRounded from '@mui/icons-material/SearchRounded';
import NotificationsNoneRounded from '@mui/icons-material/NotificationsNoneRounded';
import MenuRounded from '@mui/icons-material/MenuRounded';
import { ReactNode, useState } from 'react';
import { PageProps } from '@/types';

const nav = [
    ['Overview', '/admin'], ['Academic setup', '/admin/academics'], ['Teachers', '/admin/teachers'], ['Students', '/admin/students'],
    ['Teaching assignments', '/admin/teaching-assignments'], ['Class routines', '/admin/timetables'], ['Examinations', '/admin/examinations'], ['Fees & payments', '/admin/fee-invoices'], ['Homepage banners', '/admin/banners'],
    ['Website content', '/admin/content'], ['Notices', '/admin/notices'], ['Events', '/admin/events'], ['Facilities', '/admin/facilities'],
    ['Gallery', '/admin/galleries'], ['Admissions', '/admin/admissions'], ['Contact inbox', '/admin/contact-enquiries'],
    ['Users & roles', '/admin/users'], ['Settings', '/admin/settings'],
];

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { auth, flash } = usePage<PageProps>().props;
    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [anchor, setAnchor] = useState<null | HTMLElement>(null);
    const navigation = <List disablePadding sx={{ py: .5 }}>{nav.map(([label, href]) => <ListItemButton component={Link} href={href} onClick={() => setMobileOpen(false)} key={href} selected={location.pathname === href} title={label} sx={{ borderRadius: 2, mb: .5, px: collapsed ? 1.2 : 2, justifyContent: collapsed ? 'center' : 'flex-start', color: 'inherit', '&.Mui-selected': { bgcolor: 'rgba(255,255,255,.12)' } }}><ListItemText primary={collapsed ? label[0] : label} primaryTypographyProps={{ fontWeight: 650, fontSize: 14, noWrap: true, textAlign: collapsed ? 'center' : 'left' }} /></ListItemButton>)}</List>;

    return <Box sx={{ minHeight: '100vh', bgcolor: '#f4f6f4' }}>
        <Box component="aside" sx={{ position: 'fixed', inset: '0 auto 0 0', width: collapsed ? 84 : 260, bgcolor: 'primary.dark', color: 'white', transition: '.2s', zIndex: 1200, display: { xs: 'none', md: 'flex' }, flexDirection: 'column', overflow: 'hidden' }}>
            <Box sx={{ px: 2, pt: 2, flexShrink: 0 }}>
                <Stack direction="row" alignItems="center" spacing={1.5} sx={{ height: 64 }}><Box sx={{ width: 42, height: 42, borderRadius: '50%', bgcolor: 'secondary.main', color: 'primary.dark', display: 'grid', placeItems: 'center', fontWeight: 900 }}>SR</Box>{!collapsed && <Box><Typography fontWeight={800}>Shobuj Ridge</Typography><Typography variant="caption" color="#a9c2b9">Administration</Typography></Box>}</Stack>
                <Divider sx={{ borderColor: '#31564c', my: 2 }} />
            </Box>
            <Box sx={{ px: 2, flex: 1, minHeight: 0, overflowY: 'auto', '&::-webkit-scrollbar': { width: 6 }, '&::-webkit-scrollbar-thumb': { bgcolor: '#31564c', borderRadius: 3 } }}>{navigation}</Box>
            <Box sx={{ p: 2, pt: 1.25, flexShrink: 0, borderTop: '1px solid #31564c' }}><Button onClick={() => setCollapsed(!collapsed)} color="inherit" startIcon={<MenuRounded />} fullWidth sx={{ justifyContent: collapsed ? 'center' : 'flex-start', minWidth: 0 }}>{collapsed ? '' : 'Collapse'}</Button></Box>
        </Box>
        <Drawer open={mobileOpen} onClose={() => setMobileOpen(false)} sx={{ display: { md: 'none' }, '& .MuiDrawer-paper': { width: 280, bgcolor: 'primary.dark', color: 'white', p: 2, display: 'flex', flexDirection: 'column' } }}>
            <Typography fontWeight={850} fontSize={20} py={2}>Shobuj Ridge Admin</Typography><Divider sx={{ borderColor: '#31564c', mb: 2 }} /><Box sx={{ minHeight: 0, overflowY: 'auto' }}>{navigation}</Box>
        </Drawer>
        <Box sx={{ ml: { xs: 0, md: collapsed ? '84px' : '260px' }, transition: '.2s' }}>
            <AppBar position="sticky" color="inherit" elevation={0} sx={{ borderBottom: '1px solid', borderColor: 'divider' }}><Toolbar sx={{ gap: { xs: 1, md: 2 } }}><IconButton onClick={() => setMobileOpen(true)} aria-label="Open administration menu" sx={{ display: { md: 'none' } }}><MenuRounded /></IconButton><TextField placeholder="Search across school…" sx={{ width: { xs: '100%', md: 380 } }} InputProps={{ startAdornment: <InputAdornment position="start"><SearchRounded /></InputAdornment> }} /><Box flexGrow={1} /><IconButton aria-label="Notifications"><NotificationsNoneRounded /></IconButton><IconButton onClick={(event) => setAnchor(event.currentTarget)}><Avatar sx={{ bgcolor: 'primary.main' }}>{auth.user?.name[0]}</Avatar></IconButton><Menu anchorEl={anchor} open={!!anchor} onClose={() => setAnchor(null)}><MenuItem disabled>{auth.user?.name}</MenuItem><MenuItem component={Link} href="/profile">Profile</MenuItem><MenuItem onClick={() => router.post('/logout')}>Sign out</MenuItem></Menu></Toolbar></AppBar>
            <Container maxWidth="xl" sx={{ py: 4 }}>{flash.success && <Alert severity="success" sx={{ mb: 3 }}>{flash.success}</Alert>}{flash.error && <Alert severity="error" sx={{ mb: 3 }}>{flash.error}</Alert>}{children}</Container>
        </Box>
    </Box>;
}
