import ForWhoSection from '@/features/landing/components/ForWhoSection/ForWhoSection/ForWhoSection';
import Layout from '@/features/landing/components/Layout/Layout';
import SignUpSection from '@/features/landing/components/SignUpSection/SignUpSection/SignUpSection';
import UnlimitedIntegrationsSection from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsSection/UnlimitedIntegrationsSection';
import WhyWeSection from '@/features/landing/components/WhyWeSection/WhyWeSection/WhyWeSection';

import AboutVilnaSection from '../AboutVilnaSection/AboutVilnaSection/AboutVilnaSection';

export default function Landing() {
  return (
    <Layout>
      <AboutVilnaSection />
      <WhyWeSection />
      <ForWhoSection />
      <UnlimitedIntegrationsSection />
      <SignUpSection />
    </Layout>
  );
}
