import FaceBookIcon from '../../assets/svg/auth-section/social-media/Facebook.svg';
import GitHubIcon from '../../assets/svg/auth-section/social-media/Github.svg';
import GoogleIcon from '../../assets/svg/auth-section/social-media/Google.svg';
import TwitterIcon from '../../assets/svg/auth-section/social-media/Twitter.svg';
import { SocialLink } from '../../types/authentication/social';

export const socialLinks: SocialLink[] = [
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
