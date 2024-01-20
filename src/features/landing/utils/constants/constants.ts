import FaceBookIcon from '../../assets/img/social-media/Icons/Facebook.png';
import GitHubIcon from '../../assets/img/social-media/Icons/Github.png';
import GoogleIcon from '../../assets/img/social-media/Icons/Google.png';
import TwitterIcon from '../../assets/img/social-media/Icons/Twitter.png';
import GoogleFooterIcon from '../../assets/svg/social-icons/facebook.svg';
import GitHubFooterIcon from '../../assets/svg/social-icons/github.svg';
import FaceBookFooterIcon from '../../assets/svg/social-icons/instagram.svg';
import TwitterFooterIcon from '../../assets/svg/social-icons/linked-in.svg';
import { ISocialLink } from '../../types/social/types';

export const SIGN_UP_SECTION_ID = 'SIGN_UP_SECTION_ID';

export const SOCIAL_LINKS: ISocialLink[] = [
  {
    id: 'google-link',
    icon: GoogleIcon,
    title: 'Google',
    linkHref: '/',
  },
  {
    id: 'facebook-link',
    icon: FaceBookIcon,
    title: 'Facebook',
    linkHref: '/',
  },
  {
    id: 'github-link',
    icon: GitHubIcon,
    title: 'GitHub',
    linkHref: '/',
  },
  {
    id: 'twitter-link',
    icon: TwitterIcon,
    title: 'Twitter',
    linkHref: '/',
  },
];
export const FOOTER_SOCIAL_LINKS: ISocialLink[] = [
  {
    id: 'google-link',
    icon: FaceBookFooterIcon,
    title: 'Instagram',
    linkHref: '/',
  },
  {
    id: 'facebook-link',
    icon: GitHubFooterIcon,
    title: 'GitHub',
    linkHref: '/',
  },
  {
    id: 'github-link',
    icon: GoogleFooterIcon,
    title: 'Facebook',
    linkHref: '/',
  },
  {
    id: 'twitter-link',
    icon: TwitterFooterIcon,
    title: 'Linkedin',
    linkHref: '/',
  },
];
