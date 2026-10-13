import { Link } from '@inertiajs/react';
import { Box, Chip, Stack, Typography } from '@mui/material';
import AutoStoriesRounded from '@mui/icons-material/AutoStoriesRounded';
import VerifiedUserRounded from '@mui/icons-material/VerifiedUserRounded';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <Box sx={{ minHeight: '100vh', display: 'grid', gridTemplateColumns: { xs: '1fr', lg: 'minmax(420px, .9fr) minmax(560px, 1.1fr)' }, bgcolor: '#f7f5ee' }}>
            <Box sx={{ display: { xs: 'none', lg: 'flex' }, position: 'relative', overflow: 'hidden', flexDirection: 'column', justifyContent: 'space-between', bgcolor: 'primary.dark', color: 'white', p: { lg: 7, xl: 10 }, '&:before': { content: '""', position: 'absolute', width: 440, height: 440, borderRadius: '50%', right: -190, top: 80, border: '1px solid rgba(218,188,105,.35)' }, '&:after': { content: '"শিক্ষা"', position: 'absolute', right: -25, bottom: 90, color: 'rgba(255,255,255,.055)', fontFamily: 'Noto Serif Bengali', fontSize: 150, fontWeight: 700 } }}>
                <Stack component={Link} href="/" direction="row" spacing={1.5} alignItems="center" sx={{ color: 'inherit', textDecoration: 'none', position: 'relative', zIndex: 1 }}>
                    <Box sx={{ width: 52, height: 52, borderRadius: '50%', bgcolor: 'secondary.main', color: 'primary.dark', display: 'grid', placeItems: 'center', fontFamily: 'Newsreader', fontSize: 22, fontWeight: 800, border: '2px solid rgba(255,255,255,.5)' }}>SR</Box>
                    <Box><Typography fontWeight={850} fontSize={19}>SHOBUJ RIDGE</Typography><Typography color="#abc5bc" variant="body2">School & College · ঢাকা</Typography></Box>
                </Stack>
                <Box sx={{ position: 'relative', zIndex: 1, maxWidth: 560 }}>
                    <Chip label="SECURE SCHOOL PORTAL" icon={<VerifiedUserRounded />} sx={{ mb: 4, bgcolor: 'rgba(255,255,255,.1)', color: 'white', '& .MuiChip-icon': { color: '#d9ba68' } }} />
                    <Typography variant="h1" sx={{ fontSize: { lg: 58, xl: 70 } }}>One community.<br />Five ways to belong.</Typography>
                    <Typography sx={{ color: '#bed1ca', fontSize: 18, lineHeight: 1.75, mt: 3, maxWidth: 500 }}>A private space for administrators, teachers, guardians and students to stay connected with school life.</Typography>
                </Box>
                <Stack direction="row" spacing={1.5} alignItems="center" sx={{ position: 'relative', zIndex: 1, color: '#abc5bc' }}><AutoStoriesRounded sx={{ color: 'secondary.main' }} /><Typography variant="body2">Learning with purpose. Leading with character.</Typography></Stack>
            </Box>
            <Box sx={{ minHeight: { xs: '100vh', lg: 'auto' }, display: 'flex', flexDirection: 'column', p: { xs: 2.5, sm: 5, md: 8, xl: 12 } }}>
                <Stack component={Link} href="/" direction="row" spacing={1.2} alignItems="center" sx={{ display: { lg: 'none' }, color: 'inherit', textDecoration: 'none', mb: 6 }}>
                    <Box sx={{ width: 44, height: 44, borderRadius: '50%', bgcolor: 'primary.main', color: 'white', display: 'grid', placeItems: 'center', fontFamily: 'Newsreader', fontWeight: 800 }}>SR</Box>
                    <Box><Typography fontWeight={850}>SHOBUJ RIDGE</Typography><Typography variant="caption" color="text.secondary">School & College</Typography></Box>
                </Stack>
                <Box sx={{ width: '100%', maxWidth: 560, m: 'auto' }}>{children}</Box>
                <Typography variant="caption" color="text.secondary" textAlign="center" mt={6}>© 2026 Shobuj Ridge School & College · Need help? +880 2 5500 2244</Typography>
            </Box>
        </Box>
    );
}
