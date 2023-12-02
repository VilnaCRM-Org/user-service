import AboutVilnaSection from '../AboutVilnaSection/AboutVilnaSection/AboutVilnaSection';
import ForWhoSection from '../ForWhoSection/ForWhoSection/ForWhoSection';
import Layout from '../Layout/Layout';
import SignUpSection from '../SignUpSection/SignUpSection/SignUpSection';
import UnlimitedIntegrationsSection from '../UnlimitedIntegrationsSection/UnlimitedIntegrationsSection/UnlimitedIntegrationsSection';
import WhyWeSection from '../WhyWeSection/WhyWeSection/WhyWeSection';

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
