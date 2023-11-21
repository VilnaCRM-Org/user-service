import { Box } from '@mui/material';

import CustomLink from '@/components/ui/CustomLink/CustomLink';

const SOCIALS = [
  { id: 'instagram', url: '/assets/svg/social-icons/instagram.svg', href: '/' },
  { id: 'github', url: '/assets/svg/social-icons/github.svg', href: '/' },
  { id: 'facebook', url: '/assets/svg/social-icons/facebook.svg', href: '/' },
  { id: 'linked-in', url: '/assets/svg/social-icons/linked-in.svg', href: '/' },
];

const styles = {
  mainBox: {
    display: 'flex',
    alignItems: 'center',
    gap: '27px',
  },
  link: {},
  img: {},
};

export default function FooterSocials() {
  return (
    <Box sx={{ ...styles.mainBox }}>
      {SOCIALS.map(({ url, id }) => (
          <CustomLink key={id} href='' style={{ ...styles.link }}>
            <img src={url} alt={id} style={{ ...styles.img }} />
          </CustomLink>
        ))}
    </Box>
  );
}
