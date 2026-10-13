import PublicLayout from '@/Layouts/PublicLayout';
import { Head } from '@inertiajs/react';
import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import ArrowOutwardRounded from '@mui/icons-material/ArrowOutwardRounded';
import { Box, Card, Container, Stack, Typography } from '@mui/material';

type Facility = { id: number; title: string; description: string; photo_path?: string };

export default function Facilities({ facilities = [] }: { facilities?: Facility[] }) {
    return <PublicLayout>
        <Head title="Facilities"><meta name="description" content="Explore the spaces and services that support active, joyful learning across our school community." /></Head>
        <Box sx={{ bgcolor: '#f4f1e8', py: { xs: 9, md: 14 }, overflow: 'hidden', position: 'relative' }}>
            <Box sx={{ position: 'absolute', width: 500, height: 500, borderRadius: '50%', bgcolor: 'secondary.main', opacity: .2, right: { xs: -350, md: -120 }, top: -260 }} />
            <Container maxWidth="lg" sx={{ position: 'relative' }}><Typography variant="overline" color="secondary.dark" fontWeight={800}>OUR CAMPUS</Typography><Typography variant="h1" color="primary.dark" maxWidth={780} sx={{ fontSize: { xs: 48, md: 76 }, mt: 2 }}>Spaces made for discovery, wellbeing and belonging.</Typography><Typography color="text.secondary" fontSize={20} maxWidth={690} mt={3}>From practical laboratories to places for play, every part of our campus supports students to learn with confidence and connection.</Typography></Container>
        </Box>
        <Container maxWidth="lg" sx={{ py: { xs: 8, md: 12 } }}>
            {facilities.length ? <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: { xs: 3, md: 4 } }}>
                {facilities.map((facility, index) => <Card key={facility.id} sx={{ overflow: 'hidden', boxShadow: 'none', border: '1px solid', borderColor: 'divider', display: 'grid', gridTemplateRows: 'auto 1fr', transition: 'transform .2s ease, box-shadow .2s ease', '&:hover': { transform: 'translateY(-5px)', boxShadow: '0 16px 38px rgba(6,55,43,.12)' } }}>
                    {facility.photo_path ? <Box component="img" src={`/storage/${facility.photo_path}`} alt={facility.title} loading="lazy" sx={{ width: '100%', aspectRatio: '16 / 9', objectFit: 'cover', bgcolor: 'primary.light' }} /> : <Box sx={{ aspectRatio: '16 / 9', bgcolor: index % 2 ? '#dce9e2' : '#ece4cd', color: 'primary.dark', display: 'grid', placeItems: 'center' }}><ApartmentRounded sx={{ fontSize: 58 }} /></Box>}
                    <Box p={{ xs: 3, md: 4 }}><Stack direction="row" justifyContent="space-between" spacing={2} alignItems="flex-start"><Typography variant="h3" fontFamily="Newsreader" sx={{ fontSize: { xs: 30, md: 35 } }}>{facility.title}</Typography><ArrowOutwardRounded color="primary" /></Stack><Typography color="text.secondary" lineHeight={1.75} mt={2} whiteSpace="pre-line">{facility.description}</Typography></Box>
                </Card>)}
            </Box> : <Card sx={{ p: { xs: 4, md: 7 }, textAlign: 'center', bgcolor: '#f4f1e8', boxShadow: 'none' }}><ApartmentRounded sx={{ fontSize: 50, color: 'secondary.dark' }} /><Typography variant="h3" fontFamily="Newsreader" mt={2}>Our facilities will be shared soon.</Typography><Typography color="text.secondary" maxWidth={500} mx="auto" mt={2}>Please visit again soon to explore the spaces that support our students every day.</Typography></Card>}
            <Box sx={{ bgcolor: 'primary.dark', color: 'white', borderRadius: 3, p: { xs: 4, md: 6 }, mt: { xs: 6, md: 10 } }}><Typography variant="h3" fontFamily="Newsreader">A campus that gives every child room to grow.</Typography><Typography color="#c2d5ce" maxWidth={700} mt={2}>Thoughtful spaces and caring supervision help children feel safe to ask questions, try new things and build lasting friendships.</Typography></Box>
        </Container>
    </PublicLayout>;
}
