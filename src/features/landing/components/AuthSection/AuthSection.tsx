import { Box, Container, Stack } from '@mui/material';
import React from 'react';

import FaceBookIcon from '../../assets/img/social-media/Icons/Facebook.png';
import GitHubIcon from '../../assets/img/social-media/Icons/Github.png';
import GoogleIcon from '../../assets/img/social-media/Icons/Google.png';
import TwitterIcon from '../../assets/img/social-media/Icons/Twitter.png';

import { AuthForm } from './AuthForm';
import { SignUpText } from './SignUpText';
import { authSectionStyles } from './styles';

function AuthSection() {
  const socialLinks = [
    {
      id: 'google-link',
      icon: GoogleIcon,
      title: 'unlimited_possibilities.image_alt.google',
      linkHref: '/',
    },
    {
      id: 'facebook-link',
      icon: FaceBookIcon,
      title: 'unlimited_possibilities.image_alt.facebook',
      linkHref: '/',
    },
    {
      id: 'github-link',
      icon: GitHubIcon,
      title: 'unlimited_possibilities.image_alt.github',
      linkHref: '/',
    },
    {
      id: 'twitter-link',
      icon: TwitterIcon,
      title: 'unlimited_possibilities.image_alt.twitter',
      linkHref: '/',
    },
  ];

  return (
    <Box sx={authSectionStyles.wrapper} id="signUp" component="section">
      <Container>
        <Stack justifyContent="space-between" sx={authSectionStyles.content}>
          <SignUpText socialLinks={socialLinks} />
          <AuthForm />
        </Stack>
      </Container>
    </Box>
  );
}

export default AuthSection;
